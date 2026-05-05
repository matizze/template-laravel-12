# Contract: Gate Resolver

**Phase**: 1 — Design & Contracts
**Owns**: `Modules\Permissions\Providers\PermissionsServiceProvider::boot()` + `Modules\Permissions\Services\PermissionResolver`

## Public surface

A engine é `Gate::before` do Laravel. **Não há helper público novo.** Todos os call sites usam APIs Laravel padrão.

### Call sites suportados

```php
// Blade
@can('finance.order.create')
    <x-button>Criar Pedido</x-button>
@endcan

// Controller (recomendado: 403 automático com mensagem)
$this->authorize('finance.order.create');

// Em qualquer lugar
abort_if(! $request->user()->can('finance.order.create'), 403);

// Middleware de rota
Route::post('/orders', [OrderController::class, 'store'])
    ->middleware('can:finance.order.create');

// Direto via facade
if (Gate::allows('finance.order.create')) { ... }

// Em job, command, listener, etc.
if ($user->can('finance.order.create')) { ... }
```

> Note: nenhum desses call sites foi adicionado por esta feature. São APIs Laravel core que passam por `Gate::before` automaticamente.

## Contrato do `Gate::before`

Registrado em `PermissionsServiceProvider::boot()`:

```php
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Modules\Permissions\Services\PermissionResolver;

Gate::before(function (?Authenticatable $user, string $ability, array $arguments = []): ?bool {
    // 1. Discriminador: só intercepta abilities no formato {module}.{resource}.{action}
    if (substr_count($ability, '.') !== 2) {
        return null; // fall-through para policies/gates tradicionais
    }

    // 2. Deny-by-default: usuário não autenticado
    if (! $user) {
        return false;
    }

    // 3. Delegação ao resolver (lê Tenant::current() internamente)
    return app(PermissionResolver::class)->userHas($user, $ability);
});
```

### Inputs e outputs

| Input `$user` | Input `$ability` | Output |
|---------------|------------------|--------|
| `null` | qualquer | `null` se não-RBAC; `false` se RBAC (deny por não-autenticado) |
| `User` autenticado | `'view'` (sem 2 pontos) | `null` (fall-through) |
| `User` autenticado | `'finance.order.create'` (RBAC) | `true` se a role do user no tenant ativo concede; `false` caso contrário |
| `User` autenticado, sem `Tenant::current()` | `'finance.order.create'` | `false` (sem tenant ativo) |
| `User` autenticado, sem vínculo `tenant_user` no tenant ativo | `'finance.order.create'` | `false` (sem role) |
| `User` autenticado, vínculo com `role_id` NULL | `'finance.order.create'` | `false` (sem role) |
| `User` autenticado, ability não está no JSON da role | `'foo.bar.baz'` | `false` |

### Garantias

- **Pure**: a closure não tem efeitos colaterais. Nunca lança exceção.
- **Octane-safe**: `app(PermissionResolver::class)` é resolvido por request (scoped binding). Nenhum estado capturado.
- **Idempotente**: chamadas repetidas para o mesmo `(user, ability)` na mesma requisição retornam o mesmo valor — após primeiro hit, vem do cache.

## Contrato do `PermissionResolver`

```php
namespace Modules\Permissions\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Modules\Permissions\Models\Role;
use Modules\Tenant\Models\Tenant;

class PermissionResolver
{
    public function __construct(private CacheRepository $cache) {}

    /**
     * Returns true if the user has the named permission in the active tenant.
     */
    public function userHas(Authenticatable $user, string $permissionName): bool
    {
        $tenantId = Tenant::current()?->id;
        if ($tenantId === null) {
            return false;
        }

        $segments = explode('.', $permissionName);
        if (count($segments) !== 3) {
            return false;
        }
        [$module, $resource, $action] = $segments;

        $permissions = $this->permissionsFor($user->getKey(), $tenantId);
        $allowedActions = $permissions[$module][$resource] ?? null;

        return is_array($allowedActions)
            && in_array($action, $allowedActions, strict: true);
    }

    /**
     * Returns the cached permissions tree for (user, tenant).
     * Cache key: permissions:user:{userId}:tenant:{tenantId}
     * TTL: 60 seconds.
     *
     * @return array<string, array<string, list<string>>>
     */
    public function permissionsFor(int $userId, int|string $tenantId): array
    {
        return $this->cache->remember(
            $this->cacheKey($userId, $tenantId),
            seconds: 60,
            callback: fn () => $this->fetchFromDatabase($userId, $tenantId),
        );
    }

    public function flushUser(int $userId, int|string $tenantId): void
    {
        $this->cache->forget($this->cacheKey($userId, $tenantId));
    }

    /**
     * Flush cache for all users vinculated (via tenant_user) to the given role.
     */
    public function flushRole(Role $role): void
    {
        $userIds = $role->tenantUsers()->pluck('user_id');
        foreach ($userIds as $userId) {
            $this->flushUser($userId, $role->tenant_id);
        }
    }

    private function cacheKey(int $userId, int|string $tenantId): string
    {
        return "permissions:user:{$userId}:tenant:{$tenantId}";
    }

    /**
     * Loads role permissions from DB via the user's tenant_user link in the active tenant.
     *
     * @return array<string, array<string, list<string>>>
     */
    private function fetchFromDatabase(int $userId, int|string $tenantId): array
    {
        // Single query joining tenant_user → roles
        $role = Role::query()
            ->select('roles.permissions')
            ->join('tenant_user', 'tenant_user.role_id', '=', 'roles.id')
            ->where('tenant_user.user_id', $userId)
            ->where('tenant_user.tenant_id', $tenantId)
            ->first();

        return $role?->permissions ?? [];
    }
}
```

### Test points (PHPUnit feature)

| Cenário (do spec) | Teste | Asserção |
|-------------------|-------|----------|
| US1.1 | user com role contendo `finance.order.create` | `$user->can('finance.order.create')` é `true` |
| US1.2 | user sem essa permissão na role | `false` |
| US1.3 | sem auth | `Gate::allows('finance.order.create')` → `false` |
| US1.4 | auth mas sem `Tenant::current()` | `false` |
| US1.5 | 2 calls consecutivos | DB hit apenas no primeiro (assertar via `DB::enableQueryLog`) |
| US1.6 | depois de invalidação reativa | reflete novo estado |
| US1.7 | permissão inexistente no catálogo (typo) | `false`, sem exceção |
| Edge | vínculo `tenant_user` com `role_id` NULL | `false` |
| Edge | user vinculado a tenant A operando em B | `false` (tenant_id no WHERE não match) |

## Não-objetivos do contrato

- O contrato **não** lida com wildcards (`finance.*`). Ver Out of Scope no spec.
- O contrato **não** sabe de "permissões obsoletas". Se uma combinação foi removida do `config/permissions.php` mas ainda está num JSON de role no banco, ela continua sendo concedida (consistência eventual com o catálogo é responsabilidade operacional, não do resolver).
- O contrato **não** trata escritas paralelas no banco fora do app. Fail-secure se o cache cair (FR-026).
