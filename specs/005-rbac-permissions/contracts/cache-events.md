# Contract: Cache & Invalidation Events

**Phase**: 1 — Design & Contracts
**Owns**: `Modules\Permissions\Listeners\InvalidatePermissionCache` + `PermissionResolver` cache helpers

## Cache contract

| Aspecto | Valor |
|---------|-------|
| **Driver** | `config('cache.default')` (database em prod, array em testes) |
| **Key** | `permissions:user:{userId}:tenant:{tenantId}` |
| **TTL** | 60 segundos |
| **Value** | `array<string, array<string, list<string>>>` — JSON `permissions` da role do user no tenant ativo. Estrutura aninhada de 3 níveis: `[module => [resource => [actions]]]`. |
| **Empty case** | Sem `Tenant::current()`: resolver retorna `false` ANTES do cache; nada é gravado. Sem vínculo `tenant_user` ou `role_id` NULL: `[]` (array vazio) é gravado. |
| **Miss case** | Hit no DB via `fetchFromDatabase()`, grava resultado mesmo se vazio (evita N consultas seguidas para usuário sem role). |

## Eventos que disparam invalidação

Listener `InvalidatePermissionCache` registrado em `PermissionsServiceProvider::boot()`. Reage a:

### 1. `Role::saved`
- **Quando**: criação ou atualização de Role.
- **Detecção do que mudou**: usar `wasChanged('permissions')` para flush condicional.
  - Se mudou `permissions` ou `tenant_id` (improvável) → `flushRole($role)`.
  - Se mudou apenas `name` → no-op.
- **Ação**: `PermissionResolver::flushRole($role)` flush cache de todos os users vinculados.

### 2. `Role::deleting`
- **Quando**: role removida pelo app.
- **Ação**: `PermissionResolver::flushRole($role)` ANTES do delete (em `deleting`, ainda há vínculos para resolver).
- **Nota**: como `tenant_user.role_id` tem `restrictOnDelete`, o delete falhará se houver vínculos. Mesmo assim, o flush em `deleting` é defensivo (caso o RESTRICT seja relaxado no futuro).

### 3. `TenantUser::saved`
- **Quando**: criação de novo vínculo OU mudança em `role_id` (via `TenantUserRoleController`).
- **Detecção**:
  - Se foi criação OU `wasChanged('role_id')` → flush.
  - Outras mudanças (improváveis) → no-op.
- **Ação**: `PermissionResolver::flushUser($tenantUser->user_id, $tenantUser->tenant_id)`.

### 4. `TenantUser::deleting`
- **Quando**: vínculo revogado (operação do módulo Tenant — fora deste módulo, mas o evento Eloquent dispara aqui).
- **Ação**: `PermissionResolver::flushUser($tenantUser->user_id, $tenantUser->tenant_id)`.

## Listener implementation

```php
namespace Modules\Permissions\Listeners;

use Modules\Permissions\Models\Role;
use Modules\Permissions\Services\PermissionResolver;
use Modules\Tenant\Models\TenantUser;

class InvalidatePermissionCache
{
    public function __construct(private PermissionResolver $resolver) {}

    public function subscribe($events): void
    {
        // Role-level
        $events->listen('eloquent.saved: '.Role::class, [$this, 'whenRoleSaved']);
        $events->listen('eloquent.deleting: '.Role::class, [$this, 'whenRoleDeleting']);

        // TenantUser pivot model
        $events->listen('eloquent.saved: '.TenantUser::class, [$this, 'whenTenantUserSaved']);
        $events->listen('eloquent.deleting: '.TenantUser::class, [$this, 'whenTenantUserDeleting']);
    }

    public function whenRoleSaved(Role $role): void
    {
        if ($role->wasRecentlyCreated) {
            return; // role nova, sem vínculos ainda
        }
        if (! $role->wasChanged(['permissions', 'tenant_id'])) {
            return; // mudou apenas nome — não afeta resolução
        }
        $this->resolver->flushRole($role);
    }

    public function whenRoleDeleting(Role $role): void
    {
        $this->resolver->flushRole($role);
    }

    public function whenTenantUserSaved(TenantUser $tenantUser): void
    {
        if (! $tenantUser->wasRecentlyCreated && ! $tenantUser->wasChanged('role_id')) {
            return;
        }
        $this->resolver->flushUser($tenantUser->user_id, $tenantUser->tenant_id);
    }

    public function whenTenantUserDeleting(TenantUser $tenantUser): void
    {
        $this->resolver->flushUser($tenantUser->user_id, $tenantUser->tenant_id);
    }
}
```

Registro no provider:
```php
// PermissionsServiceProvider::boot()
$this->app['events']->subscribe(InvalidatePermissionCache::class);
```

## Falha aberta vs falha fechada do cache

Se o cache driver estiver indisponível (DB lento, Redis fora do ar):
- `cache()->remember()` lança exceção do driver.
- **Comportamento**: deixar a exceção subir (HTTP 500). Decisão fail-secure.
- **Razão**: Permissões NÃO devem ser concedidas em estado degradado (FR-026: "MUST tratar... falhas de cache... como condições de negação, nunca como condições que abrem acesso").
- Alternativa de "ignorar cache e ir direto ao banco" rejeitada — cria risco de cascata de carga se o cache estiver caindo por sobrecarga.

> Custo da decisão: instabilidade do cache vira indisponibilidade do produto. Aceito conscientemente.

## Test points (PHPUnit feature)

| Cenário | Teste |
|---------|-------|
| Cache hit no segundo acesso | `PermissionCacheTest::test_second_check_hits_cache` (DB query log assertion) |
| Invalidação ao alterar JSON da role | `PermissionCacheTest::test_updating_role_permissions_invalidates_holders` |
| Invalidação ao trocar role_id de um vínculo | `PermissionCacheTest::test_updating_tenant_user_role_id_invalidates_user` |
| Invalidação ao deletar role | `PermissionCacheTest::test_deleting_role_invalidates_holders` (testa em cenário sem RESTRICT) |
| Invalidação ao revogar vínculo | `PermissionCacheTest::test_deleting_tenant_user_invalidates_user` |
| Mudança apenas em `roles.name` não invalida | `PermissionCacheTest::test_renaming_role_does_not_flush` |
| Cross-tenant cache não vaza | `TenantIsolationTest::test_cache_keys_isolated_by_tenant` |
| Fail-secure quando cache lança | `PermissionCacheTest::test_cache_exception_propagates` (mock cache fail) |
