# Implementation Plan: RBAC Permissions System

**Branch**: `005-rbac-permissions` | **Date**: 2026-05-05 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/005-rbac-permissions/spec.md`

## Summary

Sistema RBAC com catálogo declarado em `config/permissions.php` (3 níveis: `module.resource.action`) e roles persistidas com JSON `permissions` na tabela `roles`. Cada vínculo `tenant_user` (entregue pela feature 004) recebe exatamente uma role via FK `tenant_user.role_id` — substituindo o enum `role` placeholder do 004. Verificação em runtime via `Gate::before` — call sites consomem com `$user->can('...')`, `@can` em Blade, `Gate::allows()`, `$this->authorize()` e middleware `can:`. Cache por par (user, tenant) com TTL curto e invalidação reativa em mudanças de role ou de pivot `tenant_user`. Octane-safe (sem estado entre requests).

## Technical Context

**Language/Version**: PHP 8.4
**Primary Dependencies**: Laravel 12, InterNACHI/modular (modular monolith). Consome o módulo Tenant entregue na feature 004 (Tenant model, TenantUser pivot, `Tenant::current()`). Zero deps externas adicionais.
**Storage**: SQLite em dev/test; PostgreSQL em prod. 1 tabela nova (`roles`) + 1 alteração em `tenant_user` (drop coluna `role` enum, add `role_id` FK).
**Testing**: PHPUnit 11 (feature tests). Sem Dusk para esta feature — fluxos sem dependência de Alpine/JS (formulários POST clássicos).
**Target Platform**: Web app rodando em Laravel Octane/Swoole em produção. Tenant ativo via `Tenant::current()` (helper do módulo Tenant).
**Project Type**: Modular monolith (Laravel 12 com InterNACHI/modular).
**Performance Goals**: ≤20 verificações `Gate::allows()` por requisição com custo total imperceptível ao usuário (cache do JSON `permissions` da role após primeiro hit).
**Constraints**: (1) Catálogo é arquivo PHP versionado — mudanças exigem PR + deploy (FR-003). (2) Octane: sem estado entre requests (`PermissionResolver` é `scoped`; `Tenant::current()` resolvido por request). (3) TTL curto (60s) para invalidação previsível mesmo se o cache flush do listener falhar. (4) Constituição 1.4.0 abstraiu enumeração de módulos — adicionar o módulo `permissions` é operação normal, sem violação.
**Scale/Scope**: Catálogo na ordem de centenas de combinações. Roles na ordem de dezenas por tenant. Vínculos `tenant_user` na ordem de centenas a milhares por tenant.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Check | Status | Notes |
|-------|--------|-------|
| **I. TDD** — Tests written before implementation? | ✅ | `testing-expert` agente agendado por user story. Tests/PHPUnit Feature em `app-modules/permissions/tests/Feature/`. |
| **II. Laravel Way** — Using Artisan generators, Eloquent, Form Requests, named routes? | ✅ | Eloquent para `Role`. `Gate::before` (idiomático) — sem helper global. Form Requests para todas as escritas. Rotas nomeadas. `php artisan make:*` para todos os artefatos. |
| **III. Modular Architecture** — Module boundaries respected? | ✅ | Constituição 1.4.0 abstraiu enumeração de módulos. Adicionar `app-modules/permissions/` é operação normal de desenvolvimento. Permissions depende de `modules/core` + `modules/user` + `modules/tenant`. User não importa nada de Permissions (relação via `User::tenantUsers->role` carregada normalmente; sem import direto de Role no User). |
| **IV. Simplicity** — No premature abstractions? | ✅ | Sem `TenantContext` (`Tenant::current()` direto). Sem helper global `can()` (Laravel idioms cobrem). Sem comando de sync (catálogo é arquivo PHP). Apenas 1 service (`PermissionResolver`) e 1 listener (`InvalidatePermissionCache`). |
| **V. CLAUDE.md / AGENTS.md** — Laravel Boost `search-docs` consulted? | ✅ | Implementador deve consultar `search-docs` para `Gate::before`, JSON columns em Eloquent, pivot model `belongsTo` (TenantUser→Role), cache em Octane. |
| **VI. MCP Tooling** — Serena, Boost, Herd? | ✅ | Serena para edição cirúrgica em `TenantUser` model (substituir `role` por `role_id`). Boost para search-docs. Simplify gate (`laravel-code-simplifier`) AGENDADO após implementação de cada user story. |
| **Agent Assignments** — `architect` approved? `testing-expert` scheduled? | ✅ | Architect = este step (`/speckit.plan`). `eloquent-specialist` agendado para `Role` model + migration de alter em `tenant_user`. `code-reviewer` + `security-auditor` em paralelo pré-PR. |

> Sem violações. **Complexity Tracking vazio.**

## Project Structure

### Documentation (this feature)

```text
specs/005-rbac-permissions/
├── plan.md              # Este arquivo
├── spec.md              # Spec (já existente)
├── research.md          # Phase 0 — decisões técnicas
├── data-model.md        # Phase 1 — schema, models, relacionamentos
├── quickstart.md        # Phase 1 — como usar a feature após implementação
├── contracts/           # Phase 1 — contratos (Gate, rotas, eventos)
│   ├── gate-resolver.md
│   ├── http-routes.md
│   └── cache-events.md
├── checklists/
│   └── requirements.md  # Spec quality checklist (já existente)
└── tasks.md             # Phase 2 — gerado por /speckit.tasks (NÃO criado aqui)
```

### Source Code (repository root)

```text
app-modules/permissions/                              ← MÓDULO NOVO
├── composer.json                                     # require: modules/core, modules/user, modules/tenant
├── config/
│   └── permissions.php                               # CATÁLOGO — array PHP profundidade variável
├── database/
│   ├── factories/RoleFactory.php
│   └── migrations/*_create_roles_table.php
├── resources/views/roles/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── _form.blade.php                               # checkbox tree recursivo do catálogo
├── routes/web.php                                    # Route::resource('roles', ...)
├── src/
│   ├── Models/Role.php                               # único model — name, tenant_id, permissions (JSON)
│   ├── Http/
│   │   ├── Controllers/RoleController.php            # CRUD (gated core.role.*)
│   │   └── Requests/
│   │       ├── StoreRoleRequest.php                  # validação inline (closure recursiva)
│   │       └── UpdateRoleRequest.php
│   └── Providers/PermissionsServiceProvider.php      # Gate::before + resolveRelationUsing inline (~25 linhas)
└── tests/Feature/
    ├── PermissionGateTest.php                        # US1 (todos cenários, profundidade variável)
    ├── RoleManagementTest.php                        # US2 CRUD + validação
    └── TenantUserRoleAssignmentTest.php              # US2 atribuição via TenantUserController do 004
```

**Mudanças no módulo Tenant** (entregue pelo 004):

```text
✏️  EDITAR:
   • migrations/*_create_tenant_user_table.php  → string('role') → foreignId('role_id')
   • src/Models/TenantUser.php                  → fillable; remove cast TenantRole
   • database/factories/TenantUserFactory.php   → role_id em vez de string
   • src/Http/Controllers/TenantController.php  → remove auto-Owner; remove transferOwnership; CreateTenantRequest authorize
   • src/Http/Requests/CreateTenantRequest.php  → authorize via can('core.tenant.create')
   • src/Http/Requests/UpdateTenantUserRoleRequest.php → rule role_id; authorize via can('core.role.update')
   • src/Http/Controllers/TenantUserController.php    → método de atribuição usa role_id
   • routes/web.php                             → remove /onboarding, remove transferOwnership

🗑️  DELETAR:
   • src/Http/Controllers/OnboardingController.php
   • resources/views/onboarding.blade.php
   • src/Http/Requests/TransferOwnershipRequest.php
   • src/Enums/TenantRole.php
   • Tests Feature/Browser do onboarding flow
```

**Cross-module integration points:**

- **`TenantUser → Role` via `resolveRelationUsing`**: zero import em Tenant. Em `PermissionsServiceProvider::boot()`:
  ```php
  TenantUser::resolveRelationUsing('role',
      fn ($tu) => $tu->belongsTo(\Modules\Permissions\Models\Role::class)
  );
  ```
- **Tenant**: Permissions consome `Tenant::current()` (helper estático já existente do módulo Tenant) diretamente. Sem interface intermediária.
- **Migration in-place**: A migration original do 004 (`*_create_tenant_user_table.php`) é editada diretamente — substituindo `$table->string('role')` por `$table->foreignId('role_id')->nullable()->constrained('roles')->restrictOnDelete()`. Per A4 do 004 ("Template sem produção").
- **Gate integration**: `Gate::before` em `PermissionsServiceProvider::boot()` — intercepta abilities com pelo menos 1 ponto (qualquer profundidade ≥ 2 segmentos). Outras abilities caem no fluxo normal de policies/gates.

**Decisão estrutural**: 1 model, 1 controller, 2 form requests, 1 provider (com engine inline). Sem service class, sem listener, sem cache externo, sem rule class, sem helper global, sem TenantContext, sem seeder de roles default.

**Structure Decision**: Novo módulo `permissions` em `app-modules/permissions/`, seguindo o padrão de `tenant`/`user`/`auth`/`core` (composer local, ServiceProvider, autoload `Modules\Permissions\`). Dependências do módulo: `modules/core`, `modules/user`, `modules/tenant`.

## Complexity Tracking

> Vazio. Constituição 1.4.0 abstraiu enumeração de módulos — adicionar `app-modules/permissions/` é operação normal sem violação.

## Phase 0 — Outline & Research

Decisões técnicas consolidadas em [research.md](./research.md). Itens cobertos:

- **R1**: `Gate::before` como engine única — sem helper global `can()`. Idiomas Laravel (`@can`, `Gate::allows`, `$user->can`, `$this->authorize`, middleware `can:`) cobrem todos os call sites.
- **R2**: Discriminador de "permission ability" — `substr_count($ability, '.') === 2` para evitar conflito com policies tradicionais.
- **R3**: Estratégia de cache — `cache()->remember("permissions:user:{userId}:tenant:{tenantId}", 60, ...)`. Valor: JSON `permissions` da role do user no tenant ativo.
- **R4**: Invalidação reativa — Listener único `InvalidatePermissionCache` ouvindo `Role::saved`/`Role::deleting` e pivot events em `User::tenantUsers` (mudanças de `role_id`).
- **R5**: Octane-safety — `PermissionResolver` registrado como `scoped`. `Gate::before` resolve via `app()` em runtime. Zero estado capturado.
- **R6**: Cross-module sem importação em User — User não importa nada de Permissions; navegação via `User::tenantUsers` (já existente, do 004) que tem `belongsTo(Role::class)` adicionado pela alter migration desta feature no pivot `TenantUser`.
- **R7**: Tenant context — sem interface intermediária. `Tenant::current()` (helper estático do módulo Tenant da feature 004) é consumido diretamente.
- **R8**: Validação contra `config/permissions.php` — `StoreRoleRequest`/`UpdateRoleRequest` rejeita combinações `module.resource.action` ausentes do config (FR-012/SC-007).
- **R9**: Bootstrap — `DefaultRolesSeeder` cria 4 roles padrão (Owner/Admin/Member/Viewer) em cada tenant novo. Owner recebe `core.role.manage` + `core.role.assign`. Substitui o enum `TenantRole` placeholder do 004.

## Phase 1 — Design & Contracts

Saídas:

- **[data-model.md](./data-model.md)** — Schema da nova tabela `roles`, alteração em `tenant_user` (drop `role` enum, add `role_id` FK), relacionamentos Eloquent, validações, índices.
- **[contracts/gate-resolver.md](./contracts/gate-resolver.md)** — Contrato do `Gate::before`: input (User|null, ability string), output (bool|null), regras de discriminador, deny-by-default. Code do `PermissionResolver` (lookup no JSON via `Tenant::current()`).
- **[contracts/http-routes.md](./contracts/http-routes.md)** — Rotas nomeadas, métodos, gates aplicados, payloads esperados (JSON `permissions` direto, não array de IDs).
- **[contracts/cache-events.md](./contracts/cache-events.md)** — Chave de cache, TTL, eventos que disparam invalidação, contrato do `PermissionResolver::flush*`.
- **[quickstart.md](./quickstart.md)** — Passo-a-passo para usar a feature: verificar permissão, criar role, atribuir a vínculo `tenant_user`, executar seeder de roles default.

### Agent context update

Será executado `.specify/scripts/bash/update-agent-context.sh claude` ao final do Phase 1 para registrar tecnologias novas (PHP 8.4 / Laravel 12 + InterNACHI/modular já estão; novo módulo será adicionado ao contexto).
