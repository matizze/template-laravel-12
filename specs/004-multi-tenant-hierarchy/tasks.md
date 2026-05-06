---
description: "Tasks de implementação — Multi-Tenancy Hierárquico (refactor workspace → tenant)"
---

# Tasks: Multi-Tenancy Hierárquico

**Input**: Design documents em `/specs/004-multi-tenant-hierarchy/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: Constituição § I (TDD NON-NEGOTIABLE) — testes obrigatórios antes de cada implementação. `testing-expert` é o agente que escreve cada bloco de testes.

**Organization**: por User Story. **Estratégia**: rename estrutural acontece dentro da fase de US1 (sem rename, US1 e demais quebram); fases subsequentes adicionam comportamento novo (hierarquia, criação de user, vínculos).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: paralelizável (arquivo distinto, sem dependência pendente)
- **[Story]**: rótulo de US (US1, US2, US3, US4)
- Path conventions: `app-modules/{tenant,user,auth,core}/...` (modular monolith, [plan.md](./plan.md))

> **TDD GATE**: testes MUST falhar antes de qualquer task de implementação iniciar.
> **Simplify GATE (Constituição § VI)**: após cada US verde, invocar `mcp__plugin_laravel-boost_laravel-boost__laravel-code-simplifier` antes de avançar.

---

## Phase 1: Setup

**Purpose**: preparar worktree de implementação respeitando convenção de specs em `develop`.

- [x] T001 Confirmar branch atual `develop` com tree limpo. Resolver stash pendente de `.serena/project.yml` (`git stash list` + `git stash drop` se aplicável).
- [x] T002 Criar worktree de implementação em `.worktrees/004-multi-tenant-hierarchy` pareado a uma branch `004-multi-tenant-hierarchy` derivada de `develop`. Specs **não** são copiadas para o worktree (memory rule: specs no worktree só no último commit antes do PR).
- [x] T003 [P] Dentro do worktree, rodar `composer install` para preparar deps PHP.
- [x] T004 [P] Dentro do worktree, rodar `npm install` para preparar deps frontend.
- [x] T005 [P] Verificar via Herd MCP (`mcp__herd__get_site_information`) qual é o `APP_URL` correto e atualizar `.env.dusk.local` no worktree para Dusk apontar para o site do worktree.

---

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: validações arquiteturais obrigatórias antes de tocar código. Sem essas, o restante quebra.

**⚠️ CRITICAL**: nenhuma fase de US pode começar antes que essa fase termine.

- [x] T006 Invocar agente `architect` para validar placement do refactor (módulo `tenant` substitui `workspace`; relations dinâmicas no User via `resolveRelationUsing`; nenhum import direto de Tenant em `app-modules/user`). Aprovação registrada como comentário no PR posterior.
- [x] T007 [P] Inventariar via grep todas as referências a `Workspace`, `workspace`, `WorkspaceRole`, `Member`, `Invitation`, `current_workspace`, `HasWorkspaces`, `SetCurrentWorkspace`, `CurrentWorkspaceManager` no worktree (excluindo `specs/`). Salvar contagem inicial em `tmp/refactor-baseline.txt` para comparação no fim.
- [x] T008 [P] Confirmar via `database-schema` MCP que tabela atual `users` NÃO possui coluna `current_workspace_id` (alinhar plan.md A6).
- [x] T009 Confirmar via Laravel Boost `search-docs` (queries: `pivot model events`, `belongsToMany withPivot`, `recursive cte sqlite postgresql`, `softdeletes observer`, `octane scoped binding`) que as decisões de [research.md](./research.md) refletem APIs de Laravel 12.

**Checkpoint**: arquitetura aprovada, baseline de referências capturada, decisões técnicas validadas em docs oficiais.

---

## Phase 3: User Story 1 — Operação isolada em tenant operável (Priority: P1) 🎯 MVP

**Goal**: preservar a garantia de isolamento por tenant que o módulo `workspace` já entrega, agora sob o novo nome `tenant`. Inclui o **rename estrutural completo** (FR-018) + remoção de Invitation (FR-020) já que todos os fluxos posteriores dependem dessa base limpa.

**Independent Test**: criar dois tenants raízes (folhas) A e B, vincular o mesmo user em ambos via factory, criar dado em A com sessão em A, trocar para B, confirmar que (1) listagem em B não mostra dado de A, (2) novo dado criado em B não vaza para A, (3) `grep -ri "workspace" app-modules/` retorna zero resultados.

### Tests for User Story 1 — `testing-expert` agent ⚠️ TDD GATE

> **MANDATORY**: invocar `testing-expert`. Testes MUST falhar antes da implementação.

- [x] T010 [P] [US1] `testing-expert` cria `app-modules/tenant/tests/Feature/TenantTest.php` (rename + adaptação de `WorkspaceTest.php`): asserções de isolamento (US1 AC 1, 2, 3), criação de tenant, troca via switcher. Sem hierarquia ainda.
- [x] T011 [P] [US1] `testing-expert` cria `app-modules/tenant/tests/Browser/TenantFlowTest.php` (rename + adaptação de `WorkspaceFlowTest.php`): fluxo Dusk de login → switcher → operação isolada. Usa Herd APP_URL.
- [x] T012 [P] [US1] `testing-expert` adapta `app-modules/user/tests/Feature/DeleteAccountTest.php`: setup com `Tenant`/`TenantUser` no lugar de `Workspace`/`Member`; mantém regra "owner não pode deletar conta enquanto possuir tenant onde é único Owner".
- [x] T013 [P] [US1] `testing-expert` adapta `app-modules/auth/tests/Browser/LoginFlowTest.php`: setup pós-login com tenant + tenant_user.
- [x] T014 [P] [US1] `testing-expert` adapta `app-modules/core/tests/Browser/FlashNotificationTest.php`: substitui menções a workspace nos cenários.
- [x] T015 [P] [US1] `testing-expert` adapta `app-modules/user/tests/Browser/UserManagementFlowTest.php` e `app-modules/user/tests/Browser/SettingsTabsTest.php` se houver setup workspace.
- [x] T016 [US1] Confirmar suite vermelha rodando `php artisan test --compact` — todos os testes acima MUST falhar (classes inexistentes ou sinais workspace ainda presentes).

### Implementation for User Story 1

#### Database (eloquent-specialist agent)

- [x] T017 [US1] `eloquent-specialist` renomeia migration `database/migrations/*_create_workspaces_table.php` para `*_create_tenants_table.php` (in-place edit, sem migração nova). Adiciona `parent_id` (FK self-ref nullable indexada) e `softDeletes()`. Atualiza schema da tabela: `tenants` com `id, name, slug unique, description, logo_path, parent_id, user_id, deleted_at, timestamps`.
- [x] T018 [US1] `eloquent-specialist` renomeia migration `*_create_members_table.php` para `*_create_tenant_user_table.php` (in-place). Schema: `id, user_id, tenant_id, role, timestamps` + UNIQUE(`user_id`, `tenant_id`).
- [x] T019 [US1] `eloquent-specialist` deleta migration `*_create_invitations_table.php` (FR-020).
- [x] T020 [US1] `eloquent-specialist` confirma que `database/migrations/*_create_users_table.php` permanece sem mudanças relacionadas a tenant (A6).

#### Module rename — `Serena rename_symbol` (Constituição § VI MUST use Serena)

- [x] T021 [US1] Renomear diretório `app-modules/workspace/` → `app-modules/tenant/` via `git mv`.
- [x] T022 [US1] `eloquent-specialist` renomeia model `Workspace` → `Tenant` em `app-modules/tenant/src/Models/Tenant.php` via `mcp__plugin_serena_serena__rename_symbol`. Atualiza `$table = 'tenants'`. Mantém atributos preservados (`name, slug, description, logo_path, user_id`). Métodos estáticos `current()/setCurrent()/forgetCurrent()` delegam para `CurrentTenantManager` (sem mudança semântica nesta US — hierarquia chega na US2).
- [x] T023 [US1] `eloquent-specialist` renomeia `Member` → `TenantUser` em `app-modules/tenant/src/Models/TenantUser.php`. `$table = 'tenant_user'`. Mantém `$incrementing = true`. Casts `role => TenantRole`.
- [x] T024 [US1] Deletar `app-modules/tenant/src/Models/Invitation.php` e `database/factories/InvitationFactory.php` (FR-020).
- [x] T025 [US1] Renomear enum `WorkspaceRole` → `TenantRole` em `app-modules/tenant/src/Enums/TenantRole.php` via Serena. Valores preservados (Owner/Admin/Member/Viewer).
- [x] T026 [US1] Renomear trait `HasWorkspaces` → `HasTenants` em `app-modules/tenant/src/Traits/HasTenants.php` via Serena. Métodos `roleIn(Tenant)` e `isMemberOf(Tenant)` ajustados; permanece utilitária interna ao módulo.
- [x] T027 [US1] Renomear trait `BelongsToTenant` (já existia? confirmar — se não, criar) ou criar `app-modules/tenant/src/Traits/BelongsToTenant.php` com global scope filtrando `tenant_id` da sessão + creating hook. Reusa lógica do `CurrentWorkspaceManager`.
- [x] T028 [US1] Renomear `CurrentWorkspaceManager` → `CurrentTenantManager` em `app-modules/tenant/src/Services/CurrentTenantManager.php`. Métodos `get(): ?Tenant`, `set(Tenant)`, `setById(int)`, `forget()`, `resolveFromSession()`. **Não** valida `isOperable()` ainda (chega em US2).
- [x] T029 [US1] Renomear middleware `SetCurrentWorkspace` → `SetCurrentTenant` em `app-modules/tenant/src/Http/Middleware/SetCurrentTenant.php`. Comportamento idêntico ao atual (sem leaf check ainda).
- [x] T030 [US1] Renomear policy `WorkspacePolicy` → `TenantPolicy` em `app-modules/tenant/src/Policies/TenantPolicy.php`. Manter regras existentes intactas (RBAC granular vem na spec 005).
- [x] T031 [US1] Renomear `WorkspaceServiceProvider` → `TenantServiceProvider` em `app-modules/tenant/src/Providers/TenantServiceProvider.php`. Atualizar `resolveRelationUsing` no User: `tenants()` (BelongsToMany via `tenant_user`, `using TenantUser`, `withPivot ['id','role']`) e `ownedTenants()` (HasMany por `user_id`). Registrar `CurrentTenantManager` como `scoped` (R6) com resolver closure para `session()`.
- [x] T032 [US1] Atualizar `bootstrap/providers.php`: trocar `WorkspaceServiceProvider::class` por `TenantServiceProvider::class`.
- [x] T033 [US1] Atualizar `bootstrap/app.php`: alias do middleware muda de `'workspace' => SetCurrentWorkspace::class` para `'tenant' => SetCurrentTenant::class`.
- [x] T034 [US1] Deletar `app-modules/tenant/src/Notifications/WorkspaceInviteNotification.php` (FR-020).

#### Controllers / Form Requests

- [x] T035 [US1] Renomear `WorkspaceController` → `TenantController` em `app-modules/tenant/src/Http/Controllers/TenantController.php`. Métodos `store`, `switch`, `transferOwnership` preservados. Remover qualquer referência ao fluxo de `accept invite`.
- [x] T036 [US1] Renomear `WorkspaceSettingsController` → `TenantSettingsController` em `app-modules/tenant/src/Http/Controllers/TenantSettingsController.php`. Métodos `show`, `update`, `destroy` preservados (validação de soft-delete será reforçada em US2).
- [x] T037 [US1] Renomear `MemberController` → `TenantUserController` em `app-modules/tenant/src/Http/Controllers/TenantUserController.php`. **Remover métodos `invite` e `accept`**. Manter `leave`, `updateRole`, `remove` (vão evoluir em US4).
- [x] T038 [US1] Manter `OnboardingController` (apenas atualizar imports/namespaces).
- [x] T039 [P] [US1] Renomear `CreateWorkspaceRequest` → `CreateTenantRequest`.
- [x] T040 [P] [US1] Deletar `InviteMemberRequest` (FR-020).
- [x] T041 [P] [US1] Renomear `UpdateMemberRoleRequest` → `UpdateTenantUserRoleRequest`.
- [x] T042 [P] [US1] Renomear `UpdateWorkspaceSettingsRequest` → `UpdateTenantSettingsRequest`.
- [x] T043 [P] [US1] Renomear `TransferOwnershipRequest` (manter — sem mudança semântica).

#### Views / Components / Routes

- [x] T044 [US1] Renomear componente `workspace-switcher.blade.php` → `tenant-switcher.blade.php` em `app-modules/tenant/resources/components/`. Filtro de folhas chega em US2; nesta US só rename.
- [x] T045 [US1] Renomear `create-workspace-modal.blade.php` → `create-tenant-modal.blade.php` em `app-modules/tenant/resources/components/`.
- [x] T046 [US1] Deletar `app-modules/tenant/resources/components/invite-modal.blade.php` (FR-020).
- [x] T047 [US1] Renomear views `app-modules/tenant/resources/views/dashboard/workspace-settings/` → `tenant-settings/`.
- [x] T048 [US1] Renomear `app-modules/tenant/resources/views/dashboard/members/` → `tenant-users/`. Atualizar todas as referências internas (`{{ $member }}` → `{{ $tenantUser }}`).
- [x] T049 [US1] Reescrever `app-modules/tenant/routes/web.php`: remover `/invitation/{token}` (FR-020); renomear paths de `/workspace` para `/tenant`; manter `/onboarding`, `/dashboard`, `/tenant`, `/tenant/switch/{tenant}`, `/tenant/{tenant}/users`, `/tenant/{tenant}/settings`, `/tenant/{tenant}/transfer`. Throttle preservado.

#### Cross-module updates (Constituição § III — `architect` aprovou em T006)

- [x] T050 [P] [US1] `app-modules/user/src/Http/Controllers/SettingsController.php`: substituir `Member::where('role', WorkspaceRole::Owner)` por `TenantUser::where('role', TenantRole::Owner)`; `Workspace::forgetCurrent()` por `Tenant::forgetCurrent()`. Imports atualizados.
- [x] T051 [P] [US1] `app-modules/user/src/Models/User.php`: nenhum import novo (mantém § III). Conferir que `currentWorkspace()` accessor (se existia) é renomeado para `currentTenant()` ou removido se acessado via `Tenant::current()`.
- [x] T052 [P] [US1] `app-modules/core/resources/components/layout/dashboard.blade.php` linha 19: substituir `<x-workspace::workspace-switcher :workspaces="$workspaces" :currentWorkspace="$currentWorkspace" />` por `<x-tenant::tenant-switcher :tenants="$tenants" :currentTenant="$currentTenant" />`. Atualizar view composer correspondente em `TenantServiceProvider`.

#### Factories e seeders

- [x] T053 [P] [US1] Renomear `WorkspaceFactory` → `TenantFactory` em `app-modules/tenant/database/factories/TenantFactory.php`. Adicionar states `operable()` (sem children) e `withParent(Tenant)` para uso em testes — base já preparada para US2.
- [x] T054 [P] [US1] Renomear `MemberFactory` → `TenantUserFactory`. Deletar `InvitationFactory`.

#### Verde + Simplify gate

- [x] T055 [US1] Rodar `php artisan test --compact app-modules/tenant/tests/` e suítes adaptadas em `user/auth/core` — TODAS verdes. Se vermelho em algo cross-module, invocar `debugger` agent.
- [x] T056 [US1] `vendor/bin/phpstan analyse` level 3 limpo no worktree.
- [x] T057 [US1] `vendor/bin/pint --dirty --format agent` formata todos os PHPs alterados.
- [x] T058 [US1] **Simplify gate**: invocar `mcp__plugin_laravel-boost_laravel-boost__laravel-code-simplifier` em todos os arquivos alterados na fase US1.
- [x] T059 [US1] Validar SC-003: `grep -rIi "workspace" app-modules/ --include="*.php" --include="*.blade.php"` retorna ZERO ocorrências.

**Checkpoint US1**: rename completo, isolamento preservado, suite verde, zero "workspace" em produção. MVP entregue.

---

## Phase 4: User Story 2 — Hierarquia organizacional (Priority: P1)

**Goal**: adicionar `parent_id` + comportamento estrutural (`isOperable`, `descendants`, `ancestors`) e enforcement (switcher só lista folhas, middleware bloqueia tenant não-folha, FR-009b path ancestral).

**Independent Test**: criar Matriz → Regional → Base via tinker; confirmar `isOperable()` retorna respectivamente false/false/true; switcher exibe somente Base; forçar `session()->put('tenant_id', $matriz->id)` redireciona para `/onboarding`; `descendants()` da Matriz retorna ambos descendentes em < 200ms para árvore 4×50.

### Tests for User Story 2 — `testing-expert` agent ⚠️ TDD GATE

- [x] T060 [P] [US2] `testing-expert` cria `app-modules/tenant/tests/Feature/TenantHierarchyTest.php`: asserções para US2 AC 1-5 (operable detection, switcher filter, middleware leaf check, leaf-becomes-grouper invalidation).
- [x] T061 [P] [US2] `testing-expert` cria `app-modules/tenant/tests/Feature/TenantHierarchyPerformanceTest.php` (gate de SC-005): árvore 4 níveis × 50 nós, mede `descendants()` < 200ms via `hrtime()`.
- [x] T062 [P] [US2] `testing-expert` adiciona casos a `TenantTest`: `path()` retorna caminho ancestral, FR-009a (sem unique no name), FR-017 (não pode adicionar filho a tenant com vínculos ativos).
- [x] T063 [US2] Confirmar vermelho: `php artisan test --compact --filter=Hierarchy`.

### Implementation for User Story 2

- [x] T064 [US2] `eloquent-specialist` adiciona em `Tenant.php`: `parent(): BelongsTo` (via `parent_id`), `children(): HasMany`, `descendants(): Collection<Tenant>` (via CTE recursiva R1, suporta SQLite + Postgres), `ancestors(): Collection<Tenant>`, `isOperable(): bool` (`!children()->exists()`), `path(): string` (`ancestors()->push($this)->pluck('name')->join(' › ')`).
- [x] T065 [US2] `eloquent-specialist` adiciona scope `query()->operable()` que faz `whereDoesntHave('children')`. Usado pelo switcher e validations.
- [x] T066 [US2] Atualizar `SetCurrentTenant` middleware (`handle`): inserir verificação `Tenant::find($tenantId)->isOperable()`; se falso, `session()->forget('tenant_id')` + redirect `/onboarding` com flash `warning` (R2).
- [x] T067 [US2] Atualizar `CurrentTenantManager::set()` e `setById()`: lançar exception se tenant não for operável (defesa em profundidade).
- [x] T068 [US2] Atualizar `tenant-switcher.blade.php`: usa `auth()->user()->tenants()->operable()->whereNull('deleted_at')->get()`. Renderiza `$tenant->path()` em vez de `$tenant->name` quando houver colisão (computar flag `$needsPath` no view composer).
- [x] T069 [US2] Atualizar view composer (em `TenantServiceProvider`): injeta `$tenants` (operable + linked) e `$currentTenant` no layout dashboard. Calcula `$needsPath` (FR-009b).
- [x] T070 [US2] Atualizar `CreateTenantRequest::rules()`: aceitar `parent_id` (`nullable|exists:tenants,id`); validar via custom rule `ParentHasNoActiveLinks` que parent referenciado não possui `tenant_user` ativo (FR-017) — retorna erro 422 com mensagem clara.
- [x] T071 [US2] Atualizar `create-tenant-modal.blade.php`: campo opcional `parent_id` (select com tenants existentes ou raiz).
- [x] T072 [US2] Atualizar `TenantFactory`: state `withChildren(int $count)` e helper para popular árvore de profundidade arbitrária (usado nos testes de performance).
- [x] T073 [US2] Lifecycle de tenant — soft-delete (FR-021..024):
    - Criar `app-modules/tenant/src/Http/Requests/DeleteTenantRequest.php` com pipeline de validação descrita em [contracts/tenant-crud.md](./contracts/tenant-crud.md).
    - Criar `app-modules/tenant/src/Http/Requests/RestoreTenantRequest.php` (valida pai não-deletado).
    - Criar `app-modules/tenant/src/Observers/TenantObserver.php` registrado em `TenantServiceProvider::boot`. Hooks `deleting` (FR-021) e `restoring` (FR-024).
    - Adicionar route `POST /tenant/{tenant}/restore`.
- [x] T074 [US2] Criar `app-modules/tenant/tests/Feature/TenantSoftDeleteTest.php` (testing-expert primeiro, antes de T073) cobrindo FR-021..024.
- [x] T075 [US2] Verde: `php artisan test --compact --filter=Hierarchy --filter=SoftDelete`.
- [x] T076 [US2] Performance gate: `php artisan test --compact --filter=TenantHierarchyPerformanceTest` < 200ms.
- [x] T077 [US2] PHPStan + Pint + Simplify gate (`laravel-code-simplifier`).

**Checkpoint US2**: hierarquia funcional, switcher filtra folhas, middleware bloqueia agrupador, soft-delete enforced, SC-005 gate verde.

---

## Phase 5: User Story 3 — Criação de usuário global (Priority: P2)

**Goal**: substituir o fluxo de invitation por criação direta de User global; password broker dispara reset; sem vínculo automático a tenant.

**Independent Test**: admin cria User com nome+email; verificar `User` persistido com 0 `tenant_user`; e-mail de reset enfileirado; user clica no link, define senha, loga, vê tela "aguarde atribuição de tenant".

### Tests for User Story 3 — `testing-expert` agent ⚠️ TDD GATE

- [x] T078 [P] [US3] `testing-expert` cria `app-modules/tenant/tests/Feature/UserCreationTest.php`: asserções US3 AC 1-3 + FR-013 (email único) + FR-014 (rate limit).
- [x] T079 [P] [US3] `testing-expert` cria/adapta teste para tela "no-access" (R5): user logado sem `tenant_user` cai em `/onboarding` com mensagem específica.
- [x] T080 [US3] Confirmar vermelho.

### Implementation for User Story 3

- [x] T081 [US3] Criar `app-modules/tenant/src/Http/Requests/CreateUserRequest.php` com regras `name|required|max:255`, `email|required|email|unique:users,email`. Mensagens custom em PT-BR.
- [x] T082 [US3] Criar `app-modules/tenant/src/Http/Controllers/UserCreationController.php` com método `store(CreateUserRequest $request)`:
    1. Cria `User` com password aleatório (`Str::random(40)`).
    2. Dispara `Password::broker()->sendResetLink(['email' => $user->email])`.
    3. Redirect com `->with('success', "Usuário criado. E-mail enviado para {$user->email}.")`.
- [x] T083 [US3] Adicionar rota `POST /users` em `app-modules/tenant/routes/web.php` com `throttle:5,1` (FR-014). Gate stub `users.create` (deferido para spec 005).
- [x] T084 [US3] Criar view `app-modules/tenant/resources/views/users/create.blade.php` (form simples — name, email, submit).
- [x] T085 [US3] Atualizar `app-modules/tenant/resources/views/onboarding.blade.php` com **dois estados condicionais** (R5):
    - User pode criar tenant (`Gate::allows('tenant.create')` OU sem tenants no sistema): formulário "Criar primeiro tenant".
    - User não pode: mensagem "Aguarde um administrador atribuir-lhe acesso" + botão de logout.
- [x] T086 [US3] Atualizar `SetCurrentTenant` middleware: quando user logado tem zero `tenant_user`, redirecionar para `/onboarding` (já feito em US2 — confirmar comportamento).
- [x] T087 [US3] Verde: `php artisan test --compact --filter=UserCreation`.
- [x] T088 [US3] PHPStan + Pint + Simplify gate.

**Checkpoint US3**: admin cria user → reset enviado → primeiro login funciona → user em estado "no-access".

---

## Phase 6: User Story 4 — Atribuição de vínculos `tenant_user` (Priority: P2)

**Goal**: fluxos de attach/detach/update de `TenantUser`; revogação invalida sessão just-in-time (R2).

**Independent Test**: admin atribui user existente ao tenant operável A com role Member; user vê A no switcher; admin atribui user a B; user vê A e B independentes; admin revoga A → user (com sessão em A) é redirecionado na próxima request; user mantém B.

### Tests for User Story 4 — `testing-expert` agent ⚠️ TDD GATE

- [x] T089 [P] [US4] `testing-expert` cria `app-modules/tenant/tests/Feature/TenantUserAttachTest.php`: asserções US4 AC 1-4 + FR-016 (vínculo só em operável) + invalidação just-in-time.
- [x] T090 [P] [US4] `testing-expert` adapta `TenantFlowTest` (Browser): cenário UI de attach/detach.
- [x] T091 [US4] Confirmar vermelho.

### Implementation for User Story 4

- [x] T092 [US4] Criar `app-modules/tenant/src/Http/Requests/AttachTenantUserRequest.php`:
    - `user_id|required|exists:users,id`
    - `role|required|enum:TenantRole`
    - Custom rule `IsOperableTenant` no route param `tenant` (FR-016 — 422 com mensagem específica)
    - Validação UNIQUE friendly antes do DB constraint disparar.
- [x] T093 [US4] Criar `app-modules/tenant/src/Http/Requests/DetachTenantUserRequest.php`: bloqueia remoção se `$tenantUser->role === Owner` e for o último Owner do tenant (preserva regra do `MemberController` original).
- [x] T094 [US4] Atualizar `TenantUserController` com métodos:
    - `store(AttachTenantUserRequest, Tenant $tenant)`: cria `TenantUser`.
    - `update(UpdateTenantUserRoleRequest, Tenant $tenant, TenantUser $tenantUser)`: muda role.
    - `destroy(DetachTenantUserRequest, Tenant $tenant, TenantUser $tenantUser)`: deleta.
- [x] T095 [US4] Atualizar `app-modules/tenant/routes/web.php` com:
    - `POST /tenant/{tenant}/users` → store
    - `PATCH /tenant/{tenant}/users/{tenantUser}` → update
    - `DELETE /tenant/{tenant}/users/{tenantUser}` → destroy
- [x] T096 [US4] Atualizar view `app-modules/tenant/resources/views/dashboard/tenant-users/index.blade.php`: substituir formulário de invite por formulário "Atribuir usuário existente" (autocomplete de users via campo `user_id`).
- [x] T097 [US4] Adicionar Eloquent event `creating` em `TenantUser`: confirmar `Tenant::find($tenant_id)->isOperable()` — defesa em profundidade vs Form Request (R7).
- [x] T098 [US4] Confirmar que invalidação just-in-time já funciona via `SetCurrentTenant` middleware: ao revogar vínculo, próxima request do user com sessão no tenant revogado é redirecionada (T066). Adicionar teste explícito em `TenantUserAttachTest::test_revoke_link_invalidates_active_session`.
- [x] T099 [US4] Verde: `php artisan test --compact --filter=TenantUserAttach`.
- [x] T100 [US4] Dusk: `php artisan dusk --filter=TenantFlowTest` verde com Herd APP_URL.
- [x] T101 [US4] PHPStan + Pint + Simplify gate.

**Checkpoint US4**: attach/detach/role-update funcionais; vínculo só em operáveis; revogação invalida sessão; user multi-tenant funciona com switcher.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: cleanup final, validações cross-story, gates pré-PR.

- [x] T102 Validar SC-001: rodar suite completa `php artisan test --compact` — 100% verde, zero data leak entre tenants.
- [x] T103 Validar SC-002: smoke manual via `quickstart.md` (US3 + US4 fim-a-fim < 5 min).
- [x] T104 Validar SC-003 final: `grep -rIi "workspace" app-modules/ database/ bootstrap/ config/ --include="*.php" --include="*.blade.php"` retorna ZERO. Comparar com baseline `tmp/refactor-baseline.txt`.
- [x] T105 Validar SC-004: comparar lista de testes pré-refactor vs pós-refactor; documentar testes removidos (Invitation) com justificativa em commit message.
- [x] T106 Validar SC-005: `php artisan test --filter=TenantHierarchyPerformanceTest` < 200ms p95.
- [x] T107 Validar SC-006: revisar testes de cross-tenant — `TenantTest::test_404_on_other_tenant_record_access` retorna 404 (não 403). Manipulação de sessão para tenant não-folha redireciona.
- [x] T108 Marcar `specs/002-invite-redirect-flow/spec.md` como `**Status**: Superseded by 004-multi-tenant-hierarchy` (FR-020).
- [x] T109 Atualizar `specs/001-workspace-management/spec.md` com header `**Status**: Refactored — see 004-multi-tenant-hierarchy` (linha de base preservada como referência histórica).
- [x] T110 Atualizar `CLAUDE.md` (seção Workspace → Tenant module): substituir referências de `Workspace`/`Member`/`Invitation` por `Tenant`/`TenantUser`. Atualizar `AGENTS.md` se houver menção.
- [x] T111 Atualizar `.specify/memory/constitution.md` Module Boundaries: renomear seção "Workspace" para "Tenant", atualizar lista de Models (`Tenant, TenantUser`), Dynamic Relations (`tenants(), ownedTenants()`), Trait `HasTenants`, Enum `TenantRole`, Service `CurrentTenantManager`, Middleware `SetCurrentTenant`, Controllers/Requests renomeados, Tests `TenantTest`, `TenantHierarchyTest`, `TenantUserAttachTest`, `TenantSoftDeleteTest`, `UserCreationTest`. **Constitution patch bump** (de 1.2.1 → 1.2.2).
- [x] T112 [P] Invocar `code-reviewer` agent em paralelo com `security-auditor`.
- [x] T113 [P] Invocar `security-auditor` agent em paralelo com `code-reviewer`.
- [x] T114 Endereçar feedback dos reviewers (ou registrar em comentários se inválido).
- [x] T115 `vendor/bin/pint --dirty --format agent` final.
- [x] T116 Commit final no worktree contendo specs (memory rule: specs no worktree só no último commit antes do PR). Copiar `specs/004-multi-tenant-hierarchy/` do `develop` para o worktree e incluir no commit.
- [x] T117 Push branch + abrir PR no GitHub. PR description referencia issues, success criteria, e nota a aprovação do `architect` (T006).
- [x] T118 Após merge, deletar worktree (`git worktree remove .worktrees/004-multi-tenant-hierarchy`) e branch local.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: independente, primeira coisa.
- **Foundational (Phase 2)**: depende de Setup. **BLOCKS** todas as US.
- **US1 (Phase 3)**: depende de Foundational. **BLOCKS US2/3/4** porque é o rename estrutural — sem ele, todos os outros caminhos quebram.
- **US2 (Phase 4)**: depende de US1.
- **US3 (Phase 5)**: depende de US1 (User flow não usa hierarquia, mas precisa do módulo renomeado).
- **US4 (Phase 6)**: depende de US2 (precisa de `isOperable()` para validação de vínculo) + US3 (precisa de Users criados sem vínculo prévio, mesmo que isso seja só facilitador para os testes).
- **Polish (Phase 7)**: depende de todas as US completas.

### Within Each User Story

- Testes (testing-expert) ANTES de implementação (Constituição § I).
- Models/Migrations (eloquent-specialist) antes de Controllers/Routes/Views.
- Verde + PHPStan + Pint + Simplify ANTES de avançar para próxima US.

### Parallel Opportunities

- **Phase 1**: T003, T004, T005 em paralelo.
- **Phase 2**: T007, T008 em paralelo (T006 sequencial — `architect` precisa rodar primeiro).
- **Phase 3 (US1)**:
    - Testes T010-T015 em paralelo (arquivos distintos).
    - Migrations T017-T020 em paralelo (arquivos distintos).
    - Form Requests T039-T043 em paralelo.
    - Cross-module updates T050-T052 em paralelo (após módulo `tenant` existir).
    - Factories T053-T054 em paralelo.
- **Phase 4 (US2)**: testes T060-T062 em paralelo. Implementação tem dependências sequenciais (model → middleware → views).
- **Phase 5 (US3)**: testes T078-T079 em paralelo.
- **Phase 6 (US4)**: testes T089-T090 em paralelo. Form Requests T092-T093 em paralelo.
- **Phase 7**: T112 + T113 (code-reviewer + security-auditor) em paralelo. Mandatório por Constituição.

---

## Parallel Example: User Story 1 — bloco de testes

```bash
# testing-expert escreve em paralelo (arquivos distintos):
Task: T010 — TenantTest.php (rename + adaptação WorkspaceTest)
Task: T011 — TenantFlowTest.php (Dusk)
Task: T012 — DeleteAccountTest.php (cross-module, módulo user)
Task: T013 — LoginFlowTest.php (cross-module, módulo auth)
Task: T014 — FlashNotificationTest.php (cross-module, módulo core)
Task: T015 — UserManagementFlowTest + SettingsTabsTest (cross-module, módulo user)

# Confirmar vermelho coletivo:
php artisan test --compact
```

---

## Implementation Strategy

### MVP First — Apenas US1 (rename + isolamento preservado)

1. Phase 1 (Setup) → 5 tasks.
2. Phase 2 (Foundational) → 4 tasks.
3. Phase 3 (US1) → 50 tasks (T010-T059) — o grosso do trabalho está aqui.
4. **STOP & VALIDATE**: rodar `quickstart.md` US1 manualmente. Se verde, MVP entregável (módulo renomeado, isolamento intacto, zero "workspace" em produção, Invitation removido).

### Incremental Delivery

1. Setup + Foundational + US1 → MVP shippable (renomeação completa entregue isoladamente).
2. Adicionar US2 → testar hierarquia. Shippable em separado se desejado.
3. Adicionar US3 → criar users globais. Shippable.
4. Adicionar US4 → vínculos manuais. Sistema multi-tenant pleno funcionando.
5. Polish → PR final.

### Parallel Team Strategy (não recomendada para esta feature)

Por ser refactor com renames acoplados, **executar US1 sequencialmente por uma única pessoa/agente** evita conflitos de merge. US2/3/4 podem paralelizar com cuidado (US2 toca model + middleware; US3 toca apenas user creation; US4 toca apenas TenantUserController/Request) — devs em paralelo a partir do checkpoint US1.

---

## Notes

- [P] = arquivos distintos, sem dependência pendente.
- Cada US tem TDD gate explícito (testing-expert) + simplify gate explícito (laravel-code-simplifier).
- Em qualquer task de model/migration/relation, invocar `eloquent-specialist` (Constituição § Agent Assignments).
- Em rename: SEMPRE usar `mcp__plugin_serena_serena__rename_symbol` para preservar referências (Princípio VI).
- Specs (`specs/004-multi-tenant-hierarchy/`) NÃO são copiadas para o worktree até o commit final (T116) — memory rule.
- Em qualquer falha persistente após 2 tentativas, invocar `debugger` agent (Constituição § Agent Assignments).
- Commitar em pontos lógicos (após cada US verde) para facilitar bisect se algo regredir.
