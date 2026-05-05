# Implementation Plan: Multi-Tenancy Hierárquico

**Branch**: `004-multi-tenant-hierarchy` | **Date**: 2026-05-05 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/004-multi-tenant-hierarchy/spec.md`

## Summary

Refactor do módulo `app-modules/workspace` em `app-modules/tenant`, transformando o modelo plano de **Workspace × Member × Invitation** em uma árvore de **Tenants** (com `parent_id` self-referenciada) onde a operabilidade é derivada estruturalmente (folha = operável). Usuário passa a ser entidade global; acesso vem exclusivamente do pivot `tenant_user` (modelo `TenantUser`). Conceito de Invitation é **removido**: usuário autorizado cria conta global → password broker dispara reset → atribuições de `tenant_user` são fluxo separado.

**Abordagem técnica**: rename estrutural completo (módulo, classes, tabelas, colunas, rotas, views, componentes), adição de `tenants.parent_id`, recursão via Eloquent `parent()` / `children()` com helper `descendants()` agregando uma CTE recursiva quando necessário, manutenção do isolamento via global scope `BelongsToTenant` + `SetCurrentTenant` middleware (com regra extra: tenant da sessão precisa ser folha). Migrations existentes são editadas in-place (template, sem produção). Specs `001` e `002` ficam superseded.

## Technical Context

**Language/Version**: PHP 8.4 / Laravel 12
**Primary Dependencies**: laravel/framework v12, laravel/octane v2 (Swoole), InterNACHI/modular (modular monolith), spatie/laravel-data (não usado neste módulo), blade-lucide-icons, Alpine.js 3, Tailwind v4
**Storage**: SQLite em dev/test (in-memory para PHPUnit), PostgreSQL em prod — schema único, sem partitioning. Sem alterações estruturais de stack.
**Testing**: PHPUnit 11 (Feature + Unit), Laravel Dusk 8 (Browser, Herd-backed), PHPStan/Larastan level 3, Laravel Pint
**Target Platform**: Laravel 12 modular monolith, deploy via Docker + Octane
**Project Type**: Web application (server-rendered Blade + Alpine.js, sem SPA)
**Performance Goals (SC-005 quantification)**: listagem `descendants()` da raiz em árvore com até 4 níveis × até 50 nós por nível (ordem de 200 nós) MUST renderizar em < 200ms p95 sem N+1 (medido via `php artisan test --filter=TenantHierarchyPerformanceTest`).
**Constraints**:
- Octane: zero state em singletons (usar `scoped` ou resolver closures); `CurrentTenantManager` deve ser `scoped` ou container-bound com closure que resolve da request corrente.
- Global scope nunca deve permitir queries cross-tenant em código de produção; bypass somente em comandos administrativos explicitamente marcados.
- Source de verdade do tenant ativo: **sessão** (não request, não query string).
- Sem coluna `users.current_workspace_id` no schema atual — tenant ativo já vive em sessão; spec A6 que menciona `users.current_tenant_id` será relaxada (campo NÃO será introduzido nesta spec; manter em sessão).
**Scale/Scope**:
- Módulos tocados: 4 (`tenant` (renomeado de `workspace`), `user`, `auth`, `core`).
- Arquivos renomeados/editados estimados: ~45 dentro do módulo + 8 fora.
- Migrations editadas in-place: `create_workspaces_table`, `create_members_table`, `create_invitations_table` (deletada), `create_users_table` (sem mudanças relacionadas a tenant_id).

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Check | Status | Notes |
|-------|--------|-------|
| **I. TDD** — Tests written before implementation? | ✅ | Cada tarefa em `tasks.md` terá gate de testing-expert antes da implementação. Suite atual (WorkspaceTest, InviteTest, AuthTest, etc.) será renomeada e adaptada; testes de Invitation serão removidos com justificativa explícita. |
| **II. Laravel Way** — Artisan, Eloquent, Form Requests, named routes? | ✅ | Renames aproveitam convenções (`tenant_user` é nome canônico de pivot Laravel). Form Requests preservados/renomeados. Sem raw `DB::`. Reset password broker reaproveitado (rotas `password.*` existentes). |
| **III. Modular Architecture** — Code in correct module? | ✅ | Todo o domínio multi-tenant fica em `app-modules/tenant`. User permanece sem imports diretos de Tenant — relations dinâmicas (`tenants()`, `currentTenant()`) registradas via `resolveRelationUsing()` em `TenantServiceProvider`. Trait `HasTenants` (utilitária) usada apenas internamente pelo módulo Tenant. **Cross-module change**: User module e Auth module recebem updates de teste e SettingsController — todas referências a `Workspace`/`Member` substituídas por `Tenant`/`TenantUser`. **architect agent deve validar** o ponto de extensão `resolveRelationUsing` durante `/speckit.tasks`. |
| **IV. Simplicity (YAGNI)** — Sem padrões especulativos? | ✅ | Sem nested-set / sem materialized path. Recursão via Eloquent simples + helper `descendants()` com CTE só se métricas SC-005 falharem. RBAC granular fica fora de escopo (spec separada). |
| **V. CLAUDE.md / AGENTS.md** — Laravel Boost docs consultados? | ✅ | Tasks de implementação de pivot/global scope MUST consultar `search-docs` para `pivot model`, `belongsToMany withPivot`, `Eloquent global scope`, `recursive CTE`. |
| **VI. MCP Tooling** — Serena para nav, Boost para docs/DB, Herd para URL? | ✅ | Renames MUST usar `mcp__plugin_serena_serena__rename_symbol` para evitar `find&replace` cego. Dusk tests MUST usar Herd MCP para descobrir APP_URL antes de rodar. Simplify gate (`laravel-code-simplifier`) agendado pós-implementação de cada user story. |
| **Agent Assignments** — `architect` aprovado, `testing-expert` agendado? | ✅ | architect já validou o desenho durante `/speckit.specify` + `/speckit.clarify`. testing-expert será o primeiro agent invocado em cada US no `/speckit.tasks`. |

> Nenhuma violação. **Complexity Tracking**: vazio.

## Project Structure

### Documentation (this feature)

```text
specs/004-multi-tenant-hierarchy/
├── plan.md                  # This file
├── spec.md                  # Feature specification
├── research.md              # Phase 0: hierarquia, soft-delete, session invalidation
├── data-model.md            # Phase 1: Tenant, User, TenantUser, Role
├── quickstart.md            # Phase 1: passo-a-passo de verificação
├── contracts/               # Phase 1: rotas HTTP + payloads
│   ├── tenant-crud.md
│   ├── tenant-switch.md
│   ├── tenant-user-link.md
│   └── user-creation.md
├── checklists/
│   └── requirements.md      # Pré-existente, validado
└── tasks.md                 # Phase 2 (gerado por /speckit.tasks)
```

### Source Code (repository root)

Refactor in-place do monolito modular existente. Estrutura final pós-implementação:

```text
app-modules/
├── core/                    # Inalterado (apenas dashboard.blade.php editado: x-workspace::workspace-switcher → x-tenant::tenant-switcher)
│   ├── resources/components/layout/dashboard.blade.php   ← edit
│   └── tests/Browser/FlashNotificationTest.php           ← edit (variáveis de cenário)
├── user/                    # Edits pontuais
│   ├── src/Models/User.php                               ← edit (sem imports novos; relations dinâmicas continuam vindo do TenantServiceProvider)
│   ├── src/Http/Controllers/SettingsController.php      ← edit (Member→TenantUser, WorkspaceRole→TenantRole, Workspace::forgetCurrent → Tenant::forgetCurrent)
│   ├── tests/Feature/DeleteAccountTest.php              ← edit
│   ├── tests/Browser/UserManagementFlowTest.php         ← edit
│   ├── tests/Browser/SettingsTabsTest.php               ← edit (se houver setup workspace)
│
├── auth/
│   └── tests/Browser/LoginFlowTest.php                  ← edit
│
└── tenant/                  # NOVO (renomeado de workspace/)
    ├── src/
    │   ├── Models/
    │   │   ├── Tenant.php               ← novo (de Workspace.php) + parent_id, children(), parent(), descendants(), ancestors(), isOperable()
    │   │   └── TenantUser.php           ← novo (de Member.php) — pivot model com role
    │   │   └── Invitation.php           ← REMOVIDO
    │   ├── Enums/
    │   │   └── TenantRole.php           ← rename WorkspaceRole
    │   ├── Traits/
    │   │   └── BelongsToTenant.php      ← rename / global scope + creating hook
    │   │   └── HasTenants.php           ← rename HasWorkspaces (utilitária interna)
    │   ├── Services/
    │   │   └── CurrentTenantManager.php ← rename + scoped no container, validar isOperable() em set()/setById()
    │   ├── Http/
    │   │   ├── Middleware/SetCurrentTenant.php  ← rename + adicionar verificação isOperable()
    │   │   ├── Controllers/
    │   │   │   ├── TenantController.php          ← rename WorkspaceController; sem accept invite
    │   │   │   ├── TenantSettingsController.php  ← rename
    │   │   │   ├── TenantUserController.php      ← rename MemberController; remove invite, mantém attach/detach/updateRole
    │   │   │   ├── OnboardingController.php      ← mantido (criar primeiro tenant raiz)
    │   │   │   └── UserCreationController.php    ← NOVO (US3) — cria User global, dispara password broker
    │   │   └── Requests/
    │   │       ├── CreateTenantRequest.php
    │   │       ├── AttachTenantUserRequest.php   ← rename InviteMemberRequest → semântica diferente: vincula User existente
    │   │       ├── UpdateTenantUserRoleRequest.php
    │   │       ├── TransferOwnershipRequest.php  ← preservar se ainda fizer sentido; senão remover
    │   │       ├── UpdateTenantSettingsRequest.php
    │   │       └── CreateUserRequest.php         ← NOVO (US3)
    │   ├── Notifications/
    │   │   └── WorkspaceInviteNotification.php   ← REMOVIDO
    │   ├── Policies/
    │   │   └── TenantPolicy.php                  ← rename (sem regras RBAC granulares — fica para spec 005)
    │   └── Providers/
    │       └── TenantServiceProvider.php         ← registra resolveRelationUsing tenants() / currentTenant() no User
    ├── routes/web.php                  ← reescrito: remove /invitation, /onboarding mantém, adiciona /users (criação)
    ├── resources/
    │   ├── components/
    │   │   ├── tenant-switcher.blade.php             ← rename + filtra somente folhas + exibe path ancestral em colisões
    │   │   ├── create-tenant-modal.blade.php         ← rename (com campo parent_id opcional)
    │   │   └── invite-modal.blade.php                ← REMOVIDO
    │   └── views/
    │       ├── dashboard/members/index.blade.php    ← rename para tenant-users/index.blade.php
    │       ├── dashboard/tenant-settings/...        ← rename de workspace-settings
    │       ├── onboarding.blade.php                 ← mantido
    │       └── users/create.blade.php               ← NOVO (US3)
    ├── database/
    │   ├── migrations/
    │   │   ├── XXXX_create_tenants_table.php       ← rename + adiciona parent_id (FK self-ref) + softDeletes
    │   │   ├── XXXX_create_tenant_user_table.php   ← rename create_members_table; pivot com (user_id, tenant_id, role); UNIQUE(user_id, tenant_id)
    │   │   └── XXXX_create_invitations_table.php   ← DELETADO
    │   └── factories/
    │       ├── TenantFactory.php                    ← rename + state operable() / grouper()
    │       └── TenantUserFactory.php                ← rename
    └── tests/
        ├── Feature/
        │   ├── TenantTest.php                       ← rename WorkspaceTest
        │   ├── TenantHierarchyTest.php              ← NOVO (US2)
        │   ├── TenantUserAttachTest.php             ← NOVO (US4) — substitui InviteTest
        │   ├── TenantSoftDeleteTest.php             ← NOVO (FR-021..024)
        │   ├── UserCreationTest.php                 ← NOVO (US3)
        │   └── TenantHierarchyPerformanceTest.php   ← NOVO (SC-005 gate)
        └── Browser/
            ├── TenantFlowTest.php                   ← rename WorkspaceFlowTest
            └── ModalTest.php                        ← edit (modal de invite removido; modal create-tenant + create-user)
```

**Structure Decision**: módulo único `app-modules/tenant` substitui `app-modules/workspace`. Toda a lógica multi-tenant (modelo, pivot, hierarquia, fluxos de criação de user, atribuição de vínculo, soft-delete) fica concentrada lá. User permanece como módulo agnóstico — ganha acesso a Tenant exclusivamente por relations dinâmicas registradas no boot do `TenantServiceProvider` via `resolveRelationUsing()`. Auth/Core são tocados apenas em testes e no slot do dashboard layout.

## Phase 0: Research Topics

Resolvidos em [research.md](./research.md):

1. Estratégia de traversal recursivo (Eloquent naive vs `staudenmeir/laravel-adjacency-list` vs CTE manual) — para `descendants()`/`ancestors()`.
2. Mecanismo de invalidação de sessão quando vínculo `tenant_user` é revogado ou quando tenant folha vira agrupador (US2 cenário 5, US4 cenário 3).
3. Regras concretas de soft-delete + restauração (FR-021..024) — Eloquent `SoftDeletes` + observers.
4. Quantificação de SC-005 — escolha de árvore-baseline (4 níveis × 50 nós) e gate de performance via PHPUnit timing.
5. Compatibilidade com Octane: `CurrentTenantManager` registrado como `scoped` (não `singleton`), zero state estático.

## Phase 1: Design Artifacts

Gerados após `research.md` aprovado:

- [data-model.md](./data-model.md) — entidades, atributos, validações, transições de estado (operable↔grouper, vivo↔soft-deleted).
- [contracts/](./contracts/) — quatro contratos HTTP (`tenant-crud`, `tenant-switch`, `tenant-user-link`, `user-creation`).
- [quickstart.md](./quickstart.md) — fluxo de verificação manual + comandos artisan + asserts esperados.

## Complexity Tracking

> Vazio. Constitution check passou em todos os 7 critérios.
