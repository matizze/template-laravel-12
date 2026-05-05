# Phase 1 Data Model: Multi-Tenancy Hierárquico

**Date**: 2026-05-05
**Spec**: [spec.md](./spec.md) | **Research**: [research.md](./research.md)

## Entidades

### `tenants` (modelo `Tenant`)

| Campo | Tipo | Constraint | Notas |
|-------|------|------------|-------|
| `id` | bigint unsigned | PK auto | |
| `name` | string(255) | NOT NULL | sem unique (FR-009a) |
| `slug` | string(255) | unique | preservado do Workspace para URLs limpas |
| `description` | text | nullable | preservado |
| `logo_path` | string(255) | nullable | preservado |
| `parent_id` | bigint unsigned | nullable, FK → `tenants.id` ON DELETE RESTRICT | self-ref, índice obrigatório (R1) |
| `user_id` | bigint unsigned | nullable, FK → `users.id` | "owner" do tenant (preservado do Workspace) — uso documentado, fica até spec RBAC redefinir |
| `created_at` / `updated_at` | timestamp | | |
| `deleted_at` | timestamp | nullable | `SoftDeletes` (R3) |

**Índices**: `parent_id` (recursão), `slug` (unique).

**Relations**:
- `parent(): BelongsTo` → `Tenant` via `parent_id`
- `children(): HasMany` → `Tenant` via `parent_id`
- `users(): BelongsToMany` → `User` via `tenant_user` pivot, with pivot fields `role`, `id`, timestamps
- `tenantUsers(): HasMany` → `TenantUser`
- `owner(): BelongsTo` → `User` via `user_id` (preservado)

**Métodos derivados**:
- `isOperable(): bool` — `!$this->children()->exists()` (FR-007)
- `descendants(): Collection<Tenant>` — CTE recursiva (R1)
- `ancestors(): Collection<Tenant>` — recursão simples (profundidade tipicamente baixa)
- `path(): string` — concatena `ancestors()->push($this)->pluck('name')->join(' › ')` para desambiguação UI (FR-009b)

**Estáticos preservados** (renomeados de Workspace):
- `current(): ?Tenant`, `setCurrent(Tenant|int)`, `forgetCurrent()` — delegam para `CurrentTenantManager`

**Transições de estado**:
| De | Para | Trigger | Side effect |
|----|------|---------|-------------|
| operable | grouper | `Tenant::create(['parent_id' => $self->id, ...])` | Sessões ativas com `tenant_id = $self` invalidadas just-in-time (R2) |
| grouper | operable | último filho movido/removido | Tenant volta a aparecer no switcher automaticamente |
| ativo | soft-deleted | `delete()` quando `isOperable() && users()->doesntExist() && noDomainData()` | Sumir do switcher, listings (auto via SoftDeletes) |
| soft-deleted | ativo | `restore()` quando `parent` não está soft-deleted | — |

---

### `users` (modelo `User`)

**Sem mudanças estruturais** (nenhuma coluna `current_tenant_id` adicionada — tenant ativo vive em sessão, R6).

| Campo | Tipo | Notas |
|-------|------|-------|
| `id`, `name`, `email`, `password`, `email_verified_at`, `role`, `remember_token`, timestamps | preservados | |

**Relations dinâmicas registradas em `TenantServiceProvider::boot()`** (Princípio III — User não importa Tenant):

```php
User::resolveRelationUsing('tenants', fn ($user) =>
    $user->belongsToMany(Tenant::class, 'tenant_user')
         ->using(TenantUser::class)
         ->withPivot(['id', 'role'])
         ->withTimestamps()
);
User::resolveRelationUsing('ownedTenants', fn ($user) =>
    $user->hasMany(Tenant::class, 'user_id')
);
User::resolveRelationUsing('currentTenant', fn ($user) =>
    $user->belongsTo(Tenant::class, /* ⚠ não há FK; relation virtual via session */)
);
```

> **Nota sobre `currentTenant`**: como não há coluna FK, `currentTenant()` é um **accessor** (`getCurrentTenantAttribute()`), não relation Eloquent. Implementação concreta: `Tenant::current()` (delegação ao `CurrentTenantManager`).

**Trait utilitária** (interna ao módulo, importada apenas por código de Tenant): `HasTenants` com `roleIn(Tenant): ?TenantRole` e `isMemberOf(Tenant): bool`.

---

### `tenant_user` (modelo `TenantUser` — pivot)

| Campo | Tipo | Constraint | Notas |
|-------|------|------------|-------|
| `id` | bigint unsigned | PK auto | (R7 — permite events) |
| `user_id` | bigint unsigned | NOT NULL, FK → `users.id` ON DELETE CASCADE | |
| `tenant_id` | bigint unsigned | NOT NULL, FK → `tenants.id` ON DELETE CASCADE | |
| `role` | string | NOT NULL, cast `TenantRole` | |
| `created_at` / `updated_at` | timestamp | | |

**Constraints**: UNIQUE(`user_id`, `tenant_id`) — FR-015 garante "no máximo um por par".

**Relations**: `user()`, `tenant()`.

**Validações no Form Request `AttachTenantUserRequest`**:
- `tenant_id` MUST referenciar tenant operável (folha) → custom rule `IsOperableTenant` (FR-016).
- `tenant_id` MUST não estar soft-deleted.
- Combinação (`user_id`, `tenant_id`) MUST não existir (UNIQUE no DB + validação friendly).

**Hooks (Eloquent events)**:
- `creating`: confirma `Tenant::find($tenant_id)->isOperable()`. Defesa em profundidade vs Form Request (R3 padrão).
- `deleted`: marca a sessão do user no tenant para invalidação (na verdade, just-in-time no middleware — não há ação aqui).

---

### `TenantRole` (enum)

Renomeado de `WorkspaceRole`. Valores preservados:

```php
enum TenantRole: string {
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';
}
```

> **Granularidade RBAC** (quais ações cada role pode executar) é deferida para spec **005-rbac-permissions**. Esta spec apenas reconhece a existência do enum como atributo do pivot.

---

## Tabelas removidas

- `invitations` — tabela inteira removida; migration `create_invitations_table.php` deletada (FR-020).

## Tabelas renomeadas

| De | Para | Migration |
|----|------|-----------|
| `workspaces` | `tenants` | `create_tenants_table.php` (renomeada in-place + adiciona `parent_id`, `deleted_at`) |
| `members` | `tenant_user` | `create_tenant_user_table.php` (renomeada in-place + adiciona UNIQUE) |

## Diagrama de relações

```
┌────────┐  parent_id  ┌────────┐
│ Tenant │ ◄──────────│ Tenant │  (auto-referência)
└────────┘             └────────┘
    │ ▲
    │ │ tenant_user (pivot, role)
    ▼ │
┌────────┐
│  User  │
└────────┘
```

## Validações cross-entidade

| Regra | Onde implementada |
|-------|------------------|
| Tenant com filhos não pode receber `tenant_user` | `AttachTenantUserRequest::rules()` (FR-016) |
| Tenant com vínculos `tenant_user` não pode ganhar filhos | `CreateTenantRequest::rules()` checa se `parent_id` referenciado tem zero `tenant_user` (FR-017) |
| Tenant não-folha não pode estar em sessão | `SetCurrentTenant` middleware (FR-003) |
| Soft-delete só em folha sem vínculos sem dados | `DeleteTenantRequest` + `TenantObserver::deleting` (FR-021/022) |
| Restore só se parent não-deletado | `RestoreTenantRequest::authorize` (FR-024) |
