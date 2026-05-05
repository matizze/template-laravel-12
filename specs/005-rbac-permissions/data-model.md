# Phase 1 — Data Model: RBAC Permissions System

**Feature**: 005-rbac-permissions
**Date**: 2026-05-05 (revisado após Clarifications #3)

## Overview

**1 tabela nova + 1 alteração** no schema. Catálogo vive no arquivo PHP `config/permissions.php` (não é entidade de banco). Modelo dramaticamente simplificado em relação à versão anterior (que tinha 7 tabelas).

```
config/permissions.php  ← catálogo (somente leitura)
                            └── lookup
                                 └── ValidPermissionsTree (rule)

┌────────┐         ┌──────────┐         ┌──────────────┐
│ users  │────────▶│tenant_user│────────▶│   tenants    │
│  (id)  │         │ (user_id, │         │  (id, ...)   │
└────────┘         │ tenant_id,│         └──────────────┘
                   │ role_id ←─NEW)      
                   └──────────┘
                        │
                        ▼ FK (NEW)
                   ┌──────────┐
                   │  roles   │  (NEW table)
                   │ (id,     │
                   │  tenant_id,
                   │  name,   │
                   │ permissions ← JSON)
                   └──────────┘
```

## Catálogo: `config/permissions.php`

Não é tabela. É arquivo PHP versionado no repositório:

```php
return [
    'core' => [
        'user' => ['view', 'create', 'update', 'delete'],
        'role' => ['view', 'create', 'update', 'delete', 'manage', 'assign'],
    ],
    'tenant' => [
        'tenant' => ['view', 'create', 'update', 'delete'],
        'tenant-user' => ['view', 'create', 'update', 'delete'],
    ],
    // exemplo futuro:
    // 'finance' => [
    //     'order' => ['view', 'create', 'update', 'delete', 'approve', 'cancel'],
    // ],
];
```

**Regras**:
- Sempre 3 níveis: `module` (string) → `resource` (string) → `actions` (list of strings).
- Identificadores em `[a-z][a-z0-9_-]*`, lowercase, sem espaços.
- Mudanças via PR + deploy. Removida do config: combinação não pode mais ser referenciada em novas roles, mas roles existentes continuam carregando o JSON (FR edge case).

## Tabela nova: `roles` *(R/W pelo app, gated por `core.role.manage`)*

| Coluna | Tipo | Constraints |
|--------|------|-------------|
| `id` | bigint PK auto-increment | |
| `tenant_id` | bigint FK | NOT NULL, REFERENCES `tenants(id)` ON DELETE CASCADE |
| `name` | varchar(255) | NOT NULL |
| `permissions` | json | NOT NULL, DEFAULT `'{}'` — estrutura `{module: {resource: [actions]}}` |
| `created_at` | timestamp | NULL |
| `updated_at` | timestamp | NULL |
| **UNIQUE** | (`tenant_id`, `name`) | dois roles com mesmo nome em tenants diferentes são OK |
| **INDEX** | (`tenant_id`) | queries por tenant em todo lugar |

**Notas**:
- `tenant_id` ON DELETE CASCADE: se um tenant é deletado, suas roles vão junto. É consistente com o ciclo de vida do tenant (definido pelo módulo Tenant da feature 004; soft-delete do tenant não cascateia, mas hard-delete sim).
- `permissions` JSON: estrutura aninhada de 3 níveis. Postgres usa `jsonb`; SQLite usa `JSON` (text). Sem JSON_EXTRACT em queries do resolver (lookup é em PHP após carregar).

**Migration**: `2026_05_05_000001_create_roles_table.php`

## Tabela editada in-place: `tenant_user` *(migration original do 004)*

A migration original do 004 (`app-modules/tenant/database/migrations/*_create_tenant_user_table.php`) cria `tenant_user` com colunas:
```
id, user_id, tenant_id, role (string enum), created_at, updated_at
UNIQUE(user_id, tenant_id)
```

Esta feature **edita a migration original in-place** (sem criar uma migration `alter` separada). O projeto é template sem produção (A4 do 004), então a edição destrutiva da migration é a forma idiomática.

**Mudança a aplicar na migration `*_create_tenant_user_table.php`**:

| Antes (004) | Depois (esta feature) |
|-------------|------------------------|
| `$table->string('role')->default('member');` | `$table->foreignId('role_id')->nullable()->constrained('roles')->restrictOnDelete();` |

**Estado final da migration in-place**:

```php
Schema::create('tenant_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('role_id')->nullable()->constrained('roles')->restrictOnDelete();
    $table->timestamps();

    $table->unique(['user_id', 'tenant_id']);
});
```

**Notas sobre `role_id`**:
- **NULL allowed**: representa "vínculo sem role atribuída ainda" (estado transiente, ex.: criação do vínculo antes do `DefaultRolesSeeder` ter populado roles).
- **ON DELETE RESTRICT**: bloqueia delete de uma role que ainda tem vínculos `tenant_user` apontando para ela. Força reatribuir antes (consistente com edge case do spec).

**Pré-requisito de ordem das migrations**: a migration de `roles` desta feature MUST rodar antes da migration `*_create_tenant_user_table.php` editada (que agora referencia `roles.id`). Como migrations rodam por timestamp, a `*_create_roles_table` desta feature precisa ter timestamp ANTERIOR ao da `*_create_tenant_user_table.php` do 004 — ou o implementador edita o timestamp da migration de `roles` para ficar antes.

## Eloquent Models

### `Modules\Permissions\Models\Role` (NOVO)

```php
namespace Modules\Permissions\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;

class Role extends Model
{
    protected $fillable = ['tenant_id', 'name', 'permissions'];

    protected function casts(): array
    {
        return ['permissions' => 'array'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    public function scopeForCurrentTenant(Builder $query): Builder
    {
        return $query->where('tenant_id', Tenant::current()?->id);
    }
}
```

### `Modules\Tenant\Models\TenantUser` (ALTERADO por esta feature)

```php
// Adicionar à classe existente:
use Modules\Permissions\Models\Role;

protected $fillable = ['user_id', 'tenant_id', 'role_id']; // 'role' removido

public function role(): BelongsTo
{
    return $this->belongsTo(Role::class);
}
```

> **Cross-module note**: Esta é a única coupling explícita Permissions ↔ Tenant. TenantUser passa a importar `Permissions\Models\Role`. Documentado e aceito.

### `User` (INTOCADO)

User não importa nada de Permissions. Acesso a roles via `User::tenantUsers` (relação já entregue pelo 004) que carrega TenantUser, que tem `belongsTo(Role::class)`.

## State transitions

Sem máquina de estados. CRUD direto. Mudanças observáveis pelo cache:

| Evento | Disparado por | Cache afetado |
|--------|---------------|---------------|
| `Role::created` | App (novo role) ou seeder | Nenhum (role nova, sem vínculos ainda) |
| `Role::updated` (mudança no JSON `permissions`) | App | Cache de TODOS os users vinculados a essa role no tenant |
| `Role::updated` (mudança apenas em `name`) | App | Nenhum (não afeta resolução) |
| `Role::deleting` | App ou cascade do tenant | Cache de TODOS os users vinculados ANTES do delete |
| `TenantUser::created` (novo vínculo) | Módulo Tenant | Cache do `(user_id, tenant_id)` específico |
| `TenantUser::updated` (mudança em `role_id`) | App via `TenantUserRoleController` | Cache do `(user_id, tenant_id)` específico |
| `TenantUser::deleting` (vínculo revogado) | Módulo Tenant | Cache do `(user_id, tenant_id)` específico |

Ver [contracts/cache-events.md](./contracts/cache-events.md) para o contrato exato.

## Validações (Form Requests)

### `StoreRoleRequest`

```php
public function authorize(): bool
{
    return $this->user()->can('core.role.manage');
}

public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255',
            Rule::unique('roles')->where('tenant_id', Tenant::current()?->id)],
        'permissions' => ['required', 'array', new ValidPermissionsTree],
    ];
}
```

### `UpdateRoleRequest`

Idêntico ao Store, mas a regra unique ignora o role atual:
```php
Rule::unique('roles')->where('tenant_id', Tenant::current()?->id)
    ->ignore($this->route('role'))
```

### `AssignTenantUserRoleRequest`

```php
public function authorize(): bool
{
    return $this->user()->can('core.role.assign');
}

public function rules(): array
{
    return [
        'role_id' => ['required', 'integer',
            Rule::exists('roles', 'id')->where('tenant_id', Tenant::current()?->id)],
    ];
}
```

### Rule: `ValidPermissionsTree`

```php
namespace Modules\Permissions\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPermissionsTree implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('Permissions must be an array.');
            return;
        }
        $catalog = config('permissions');
        foreach ($value as $module => $resources) {
            if (! is_array($resources) || ! isset($catalog[$module])) {
                $fail("Module '{$module}' does not exist in catalog.");
                return;
            }
            foreach ($resources as $resource => $actions) {
                if (! is_array($actions) || ! isset($catalog[$module][$resource])) {
                    $fail("Resource '{$module}.{$resource}' does not exist in catalog.");
                    return;
                }
                foreach ($actions as $action) {
                    if (! in_array($action, $catalog[$module][$resource], true)) {
                        $fail("Action '{$module}.{$resource}.{$action}' does not exist in catalog.");
                        return;
                    }
                }
            }
        }
    }
}
```

## Factories (testes)

```
RoleFactory:
  - tenant_id: associated Tenant factory
  - name: $faker->company().' Role'
  - permissions: state methods:
    - empty()           → []
    - withFullCatalog() → todo o config/permissions.php
    - withCore()        → apenas { core: { ... } }
    - withSubset(array) → user-defined subset
```

`TenantUserFactory` (do 004) precisa ajustar o atributo `role` (enum) para `role_id` (FK Role) — pode usar `Role::factory()->create()`.

## Índices e performance

- `roles.tenant_id` — INDEX (queries forCurrentTenant em todo lugar).
- `roles.permissions` — sem index. Lookup é em PHP após carregar (resolver). Sem queries JSON.
- `tenant_user(role_id)` — INDEX recomendado para `Role::tenantUsers` (queries reversas para invalidação de cache).

## O que NÃO existe (importante)

| Item | Status | Razão |
|------|--------|-------|
| Tabela `modules` | ❌ removida | catálogo em config |
| Tabela `resources` | ❌ removida | catálogo em config |
| Tabela `actions` | ❌ removida | catálogo em config |
| Tabela `permissions` | ❌ removida | catálogo em config |
| Tabela pivot `role_permission` | ❌ removida | substituída por JSON `roles.permissions` |
| Tabela pivot `role_user` | ❌ removida | substituída por `tenant_user.role_id` |
| Coluna `tenant_user.role` (enum) | ❌ removida | substituída por `tenant_user.role_id` (FK) |
| Enum `TenantRole` (Owner/Admin/Member/Viewer) | ⚠ deprecated | era placeholder do 004; vira nome de roles default seedeadas |
| Interface `TenantContext` | ❌ removida | `Tenant::current()` consumido direto |
| Helper global `can()` | ❌ removido | idiomas Laravel cobrem |
