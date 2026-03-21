# Tasks: Modular Monolith Migration

**Input**: Design documents from `/specs/003-modular-migration/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md

**Tests**: Os testes existentes servem como safety net (refactor). Novos testes apenas para enforcement de dependências (US6).

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

---

## Phase 1: Setup — Criar infraestrutura modular (US1, Priority: P1)

**Goal**: Criar a estrutura de módulos com Composer path repositories e Laravel package discovery. Zero libs externas.

**Independent Test**: `composer dump-autoload` resolve namespaces. ServiceProviders são auto-descobertos. Todos os testes passam.

- [x] T001 [US1] Criar directórios base dos 4 módulos: `mkdir -p app-modules/{core,user,auth,workspace}/{src/Providers,routes,resources,tests,database}`
- [x] T002 [US1] Criar `app-modules/core/composer.json` com PSR-4 `Modules\Core\` e provider discovery para `CoreServiceProvider`
- [x] T003 [P] [US1] Criar `app-modules/user/composer.json` com PSR-4 `Modules\User\` e provider discovery para `UserServiceProvider`
- [x] T004 [P] [US1] Criar `app-modules/auth/composer.json` com PSR-4 `Modules\Auth\` e provider discovery para `AuthServiceProvider`
- [x] T005 [P] [US1] Criar `app-modules/workspace/composer.json` com PSR-4 `Modules\Workspace\` e provider discovery para `WorkspaceServiceProvider`
- [x] T006 [US1] Registar os 4 path repositories no root `composer.json` e adicionar requires `modules/*:*@dev`
- [x] T007 [US1] Criar `app-modules/core/src/Providers/CoreServiceProvider.php` (vazio, só extends ServiceProvider com boot/register)
- [x] T008 [P] [US1] Criar `app-modules/user/src/Providers/UserServiceProvider.php` (vazio)
- [x] T009 [P] [US1] Criar `app-modules/auth/src/Providers/AuthServiceProvider.php` (vazio)
- [x] T010 [P] [US1] Criar `app-modules/workspace/src/Providers/WorkspaceServiceProvider.php` (vazio)
- [x] T011 [US1] Executar `composer update` para resolver os path repositories
- [x] T012 [US1] Adicionar test suites dos módulos ao `phpunit.xml` (Core, User, Auth, Workspace)
- [x] T013 [US1] Executar `php artisan test --compact` e confirmar que todos os testes passam

**Checkpoint**: Infraestrutura modular criada. 4 módulos com composer.json, ServiceProviders auto-descobertos. Testes passam.

---

## Phase 2: Migrar módulo Core (US2, Priority: P1)

**Goal**: Extrair AppServiceProvider, componentes Blade genéricos, layouts e dashboard para o módulo Core.

**Independent Test**: A aplicação arranca. Layouts renderizam. Componentes `<x-button>`, `<x-card>`, etc. funcionam sem prefixo. Todos os testes passam.

### Implementation for User Story 2

- [x] T014 [US2] Mover AppServiceProvider para `app-modules/core/src/Providers/CoreServiceProvider.php` — merge conteúdo do AppServiceProvider existente com o CoreServiceProvider criado, actualizar namespace para `Modules\Core\Providers`
- [x] T015 [US2] Remover `App\Providers\AppServiceProvider` de `bootstrap/providers.php` (agora auto-descoberto via package discovery)
- [x] T016 [US2] Configurar CoreServiceProvider `boot()` com `Blade::anonymousComponentPath()` para registar componentes sem prefixo
- [x] T017 [P] [US2] Mover `resources/views/components/button.blade.php` para `app-modules/core/resources/components/button.blade.php`
- [x] T018 [P] [US2] Mover `resources/views/components/card.blade.php` para `app-modules/core/resources/components/card.blade.php`
- [x] T019 [P] [US2] Mover `resources/views/components/modal.blade.php` para `app-modules/core/resources/components/modal.blade.php`
- [x] T020 [P] [US2] Mover `resources/views/components/avatar.blade.php` para `app-modules/core/resources/components/avatar.blade.php`
- [x] T021 [P] [US2] Mover `resources/views/components/pagination.blade.php` para `app-modules/core/resources/components/pagination.blade.php`
- [x] T022 [P] [US2] Mover `resources/views/components/nav-item.blade.php` para `app-modules/core/resources/components/nav-item.blade.php`
- [x] T023 [P] [US2] Mover `resources/views/components/user-menu.blade.php` para `app-modules/core/resources/components/user-menu.blade.php`
- [x] T024 [P] [US2] Mover `resources/views/components/form/` (input, select, textarea) para `app-modules/core/resources/components/form/`
- [x] T025 [P] [US2] Mover `resources/views/components/icon/` para `app-modules/core/resources/components/icon/`
- [x] T026 [P] [US2] Mover `resources/views/components/layout/` (app, dashboard) para `app-modules/core/resources/components/layout/`
- [x] T027 [US2] Mover `resources/views/dashboard.blade.php` para `app-modules/core/resources/views/dashboard.blade.php` e adicionar `@yield('dashboard-content')` com mensagem de boas-vindas padrão
- [x] T028 [US2] Registar view path do Core no CoreServiceProvider via `$this->loadViewsFrom()`
- [x] T029 [US2] Mover `tests/Browser/FlashNotificationTest.php` para `app-modules/core/tests/Browser/FlashNotificationTest.php` e actualizar namespace
- [x] T030 [US2] Executar `php artisan test --compact` e confirmar que todos os testes passam

**Checkpoint**: Core migrado. Componentes renderizam sem prefixo. Dashboard tem slots. Testes passam.

---

## Phase 3: Migrar módulo User (US3, Priority: P1)

**Goal**: Extrair User model (limpo), controllers, requests, comando, views de settings para o módulo User.

**Independent Test**: CRUD de utilizadores funciona. Settings de perfil/password funcionam. `php artisan create:user` funciona. Testes em `app-modules/user/tests/` passam.

### Implementation for User Story 3

- [x] T031 [US3] Mover User model para `app-modules/user/src/Models/User.php` com namespace `Modules\User\Models` — remover métodos de workspace (workspaces(), ownedWorkspaces(), roleIn(), isMemberOf(), roleCache)
- [x] T032 [US3] Actualizar `config/auth.php` para referenciar `Modules\User\Models\User::class`
- [x] T033 [US3] Actualizar todas as referências `App\Models\User` para `Modules\User\Models\User` no projecto
- [x] T034 [P] [US3] Mover SettingsController para `app-modules/user/src/Http/Controllers/SettingsController.php` com namespace `Modules\User\Http\Controllers`
- [x] T035 [P] [US3] Mover UserController para `app-modules/user/src/Http/Controllers/UserController.php` com namespace `Modules\User\Http\Controllers`
- [x] T036 [P] [US3] Mover DeleteAccountRequest para `app-modules/user/src/Http/Requests/DeleteAccountRequest.php` com namespace `Modules\User\Http\Requests`
- [x] T037 [P] [US3] Mover StoreUserRequest para `app-modules/user/src/Http/Requests/StoreUserRequest.php`
- [x] T038 [P] [US3] Mover UpdatePasswordRequest para `app-modules/user/src/Http/Requests/UpdatePasswordRequest.php`
- [x] T039 [P] [US3] Mover UpdateProfileRequest para `app-modules/user/src/Http/Requests/UpdateProfileRequest.php`
- [x] T040 [P] [US3] Mover UpdateUserRoleRequest para `app-modules/user/src/Http/Requests/UpdateUserRoleRequest.php`
- [x] T041 [US3] Mover CreateUserCommand para `app-modules/user/src/Console/Commands/CreateUserCommand.php` com namespace `Modules\User\Console\Commands`
- [x] T042 [US3] Deprecar AuthorizationServiceProvider — usar auto-discovery de Policies do Laravel
- [x] T043 [P] [US3] Mover views `resources/views/settings/` para `app-modules/user/resources/views/settings/`
- [x] T044 [US3] Configurar UserServiceProvider `boot()` com `loadViewsFrom()`, `loadRoutesFrom()`, `loadMigrationsFrom()`, e registo de commands
- [x] T045 [US3] Extrair rotas de user/settings de `routes/web.php` para `app-modules/user/routes/web.php`
- [x] T046 [P] [US3] Mover migrations `create_users_table` e `create_password_reset_tokens_table` para `app-modules/user/database/migrations/`
- [x] T047 [P] [US3] Mover UserFactory para `app-modules/user/database/factories/UserFactory.php` com namespace `Modules\User\Database\Factories`
- [x] T048 [P] [US3] Mover testes Feature: UserManagementTest, SettingsTest, DeleteAccountTest, CreateUserCommandTest para `app-modules/user/tests/Feature/` e actualizar namespaces
- [x] T049 [P] [US3] Mover testes Dusk: SettingsTabsTest, UserManagementFlowTest para `app-modules/user/tests/Browser/`
- [x] T050 [US3] Executar `php artisan test --compact` e confirmar que todos os testes passam

**Checkpoint**: User migrado. Model limpo. Auth config actualizado. Settings e admin CRUD funcionam. Testes passam.

---

## Phase 4: Migrar módulo Auth (US4, Priority: P2)

**Goal**: Extrair controllers, requests, views e rotas de autenticação para o módulo Auth.

**Independent Test**: Login, registo, forgot/reset password funcionam. Testes em `app-modules/auth/tests/` passam.

### Implementation for User Story 4

- [x] T051 [P] [US4] Mover LoginController para `app-modules/auth/src/Http/Controllers/LoginController.php` com namespace `Modules\Auth\Http\Controllers`
- [x] T052 [P] [US4] Mover RegisterController para `app-modules/auth/src/Http/Controllers/RegisterController.php`
- [x] T053 [P] [US4] Mover ForgotPasswordController para `app-modules/auth/src/Http/Controllers/ForgotPasswordController.php`
- [x] T054 [P] [US4] Mover ResetPasswordController para `app-modules/auth/src/Http/Controllers/ResetPasswordController.php`
- [x] T055 [P] [US4] Mover MakeLoginRequest para `app-modules/auth/src/Http/Requests/MakeLoginRequest.php` com namespace `Modules\Auth\Http\Requests`
- [x] T056 [P] [US4] Mover MakeRegisterRequest para `app-modules/auth/src/Http/Requests/MakeRegisterRequest.php`
- [x] T057 [P] [US4] Mover ForgotPasswordRequest para `app-modules/auth/src/Http/Requests/ForgotPasswordRequest.php`
- [x] T058 [P] [US4] Mover ResetPasswordRequest para `app-modules/auth/src/Http/Requests/ResetPasswordRequest.php`
- [x] T059 [US4] Mover `routes/auth.php` para `app-modules/auth/routes/auth.php` e registar no AuthServiceProvider via `loadRoutesFrom()`
- [x] T060 [US4] Actualizar `bootstrap/app.php` para não carregar `routes/auth.php` directamente
- [x] T061 [P] [US4] Mover views `resources/views/auth/` para `app-modules/auth/resources/views/auth/`
- [x] T062 [US4] Configurar AuthServiceProvider `boot()` com `loadViewsFrom()` e `loadRoutesFrom()`
- [x] T063 [P] [US4] Mover testes Feature: AuthTest, PasswordResetTest para `app-modules/auth/tests/Feature/`
- [x] T064 [P] [US4] Mover testes Dusk: LoginFlowTest, RegisterFlowTest, ForgotPasswordFlowTest, ValidationTest para `app-modules/auth/tests/Browser/`
- [x] T065 [US4] Executar `php artisan test --compact` e confirmar que todos os testes passam

**Checkpoint**: Auth migrado. Login, registo, password reset funcionam. Rotas carregadas pelo módulo. Testes passam.

---

## Phase 5: Migrar módulo Workspace (US5, Priority: P2)

**Goal**: Extrair toda a lógica de workspaces. Criar trait HasWorkspaces. Componentes com prefixo `<x-workspace::*>`.

**Independent Test**: Workspaces, membros, convites, onboarding funcionam. Testes em `app-modules/workspace/tests/` passam.

### Implementation for User Story 5

- [x] T066 [P] [US5] Mover Workspace model para `app-modules/workspace/src/Models/Workspace.php` com namespace `Modules\Workspace\Models`
- [x] T067 [P] [US5] Mover Member model para `app-modules/workspace/src/Models/Member.php`
- [x] T068 [P] [US5] Mover Invitation model para `app-modules/workspace/src/Models/Invitation.php`
- [x] T069 [P] [US5] Mover WorkspaceRole enum para `app-modules/workspace/src/Enums/WorkspaceRole.php`
- [x] T070 [US5] Criar trait `HasWorkspaces` em `app-modules/workspace/src/Traits/HasWorkspaces.php` com métodos extraídos do User: workspaces(), ownedWorkspaces(), roleIn(), isMemberOf(), roleCache
- [x] T071 [US5] Adicionar `use \Modules\Workspace\Traits\HasWorkspaces;` ao User model em `app-modules/user/src/Models/User.php`
- [x] T072 [US5] Remover trait `BelongsToWorkspace` de `app/Traits/` (substituída por HasWorkspaces)
- [x] T073 [P] [US5] Mover WorkspacePolicy para `app-modules/workspace/src/Policies/WorkspacePolicy.php`
- [x] T074 [P] [US5] Mover CurrentWorkspaceManager para `app-modules/workspace/src/Services/CurrentWorkspaceManager.php`
- [x] T075 [P] [US5] Mover SetCurrentWorkspace middleware para `app-modules/workspace/src/Http/Middleware/SetCurrentWorkspace.php`
- [x] T076 [US5] Actualizar referência do middleware em `bootstrap/app.php` para `Modules\Workspace\Http\Middleware\SetCurrentWorkspace`
- [x] T077 [P] [US5] Mover WorkspaceController para `app-modules/workspace/src/Http/Controllers/WorkspaceController.php`
- [x] T078 [P] [US5] Mover WorkspaceSettingsController para `app-modules/workspace/src/Http/Controllers/WorkspaceSettingsController.php`
- [x] T079 [P] [US5] Mover MemberController para `app-modules/workspace/src/Http/Controllers/MemberController.php`
- [x] T080 [P] [US5] Mover OnboardingController para `app-modules/workspace/src/Http/Controllers/OnboardingController.php`
- [x] T081 [P] [US5] Mover CreateWorkspaceRequest para `app-modules/workspace/src/Http/Requests/CreateWorkspaceRequest.php`
- [x] T082 [P] [US5] Mover InviteMemberRequest para `app-modules/workspace/src/Http/Requests/InviteMemberRequest.php`
- [x] T083 [P] [US5] Mover UpdateMemberRoleRequest para `app-modules/workspace/src/Http/Requests/UpdateMemberRoleRequest.php`
- [x] T084 [P] [US5] Mover TransferOwnershipRequest para `app-modules/workspace/src/Http/Requests/TransferOwnershipRequest.php`
- [x] T085 [P] [US5] Mover UpdateWorkspaceSettingsRequest para `app-modules/workspace/src/Http/Requests/UpdateWorkspaceSettingsRequest.php`
- [x] T086 [P] [US5] Mover WorkspaceInviteNotification para `app-modules/workspace/src/Notifications/WorkspaceInviteNotification.php`
- [x] T087 [US5] Extrair rotas de workspace de `routes/web.php` para `app-modules/workspace/routes/web.php`
- [x] T088 [P] [US5] Mover views `resources/views/dashboard/` para `app-modules/workspace/resources/views/dashboard/`
- [x] T089 [P] [US5] Mover `resources/views/onboarding.blade.php` para `app-modules/workspace/resources/views/onboarding.blade.php`
- [x] T090 [P] [US5] Mover componentes workspace-switcher, create-workspace-modal, invite-modal para `app-modules/workspace/resources/components/`
- [x] T091 [US5] Actualizar referências nas views: `<x-workspace-switcher>` → `<x-workspace::workspace-switcher>`, `<x-create-workspace-modal>` → `<x-workspace::create-workspace-modal>`, `<x-invite-modal>` → `<x-workspace::invite-modal>`
- [x] T092 [US5] Configurar WorkspaceServiceProvider `boot()` com `loadViewsFrom()`, `loadRoutesFrom()`, `loadMigrationsFrom()`, `Blade::anonymousComponentNamespace()`
- [x] T093 [US5] Actualizar todas as referências `App\Models\Workspace`, `App\Models\Member`, `App\Models\Invitation`, `App\Enums\WorkspaceRole`, etc. para `Modules\Workspace\*`
- [x] T094 [P] [US5] Mover migrations create_workspaces_table, create_members_table, create_invitations_table para `app-modules/workspace/database/migrations/`
- [x] T095 [P] [US5] Mover factories WorkspaceFactory, MemberFactory, InvitationFactory para `app-modules/workspace/database/factories/`
- [x] T096 [P] [US5] Mover testes Feature: WorkspaceTest, InviteTest para `app-modules/workspace/tests/Feature/`
- [x] T097 [P] [US5] Mover testes Dusk: WorkspaceFlowTest, ModalTest para `app-modules/workspace/tests/Browser/`
- [x] T098 [US5] Executar `php artisan test --compact` e confirmar que todos os testes passam

**Checkpoint**: Workspace migrado. HasWorkspaces trait funciona. Componentes com prefixo. Testes passam.

---

## Phase 6: Desacoplamento verificável (US6, Priority: P3)

**Goal**: Verificar que Workspace pode ser removido. Criar scripts de enforcement de dependências.

**Independent Test**: Script CI + PHPStan validam dependências. Remover Workspace → app arranca, testes Core/User/Auth passam.

### Implementation for User Story 6

- [x] T099 [US6] Criar script CI `scripts/check-module-dependencies.sh` que analisa `use Modules\*` statements e valida contra regras permitidas
- [x] T100 [US6] Criar regra PHPStan customizada em `phpstan/ModuleDependencyRule.php` que falha em violações de dependência
- [x] T101 [US6] Adicionar regra PHPStan à configuração `phpstan.neon`
- [x] T102 [US6] Executar script de enforcement e confirmar zero violações
- [x] T103 [US6] Executar PHPStan e confirmar zero violações de dependência
- [x] T104 [US6] Testar desacoplamento: remover `use HasWorkspaces` do User model, remover `app-modules/workspace/`, remover entries do root `composer.json`
- [x] T105 [US6] Verificar que `php artisan test app-modules/core/tests/ app-modules/user/tests/ app-modules/auth/tests/` passa
- [x] T106 [US6] Reverter remoção do Workspace (restore via git)
- [x] T107 [US6] Executar `php artisan test --compact` final — todos os testes passam

**Checkpoint**: Enforcement funciona. Workspace é removível. Modularização validada.

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Limpeza final, formatação, e quality gates.

- [x] T108 Invocar `mcp__plugin_laravel-boost_laravel-boost__laravel-code-simplifier` em todos os ficheiros PHP alterados (Constitution VI — NON-NEGOTIABLE gate)
- [x] T109 Medir tempo de boot com `time php artisan route:list` e comparar (SC-004: <10% aumento)
- [x] T110 Verificar directório `app/` raiz — manter `app/Http/Controllers/Controller.php` e ficheiros que o Laravel exige, confirmar que migrados foram removidos incrementalmente
- [x] T111 Limpar `routes/web.php` — confirmar que rotas migradas foram removidas
- [x] T112 Remover `app/Providers/AuthorizationServiceProvider.php` e `app/Providers/AppServiceProvider.php` (migrados para módulos)
- [x] T113 Actualizar `CLAUDE.md` com nova estrutura modular e convenções de namespace
- [x] T114 Executar `vendor/bin/pint --dirty --format agent` para formatar ficheiros PHP
- [x] T115 Executar `./vendor/bin/phpstan analyse` e corrigir erros
- [x] T116 Executar `php artisan test --compact` — verificação final completa
- [x] T117 [P] Invocar `code-reviewer` agent nos ficheiros alterados
- [x] T118 [P] Invocar `security-auditor` agent nos ficheiros alterados

**Checkpoint**: Projecto limpo. Formatação correcta. PHPStan sem erros. Todos os testes passam.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Phase 1 (Setup/US1)**: Sem dependências — pode começar imediatamente
- **Phase 2 (Core/US2)**: Depende de Phase 1 — BLOQUEIA todos os outros módulos
- **Phase 3 (User/US3)**: Depende de Phase 2 — BLOQUEIA Auth e Workspace
- **Phase 4 (Auth/US4)**: Depende de Phase 3 — pode correr em PARALELO com Phase 5
- **Phase 5 (Workspace/US5)**: Depende de Phase 3 — pode correr em PARALELO com Phase 4
- **Phase 6 (Desacoplamento/US6)**: Depende de Phase 4 + Phase 5
- **Phase 7 (Polish)**: Depende de todas as fases anteriores

### User Story Dependencies

```
US1 (Setup) → US2 (Core) → US3 (User) → US4 (Auth) [P] US5 (Workspace) → US6 (Verification) → Polish
```

### Parallel Opportunities

**Phase 1**: T003, T004, T005 (composer.json dos módulos) e T008, T009, T010 (ServiceProviders)
**Phase 2**: T017-T026 (mover componentes)
**Phase 3**: T034-T040, T043, T046-T049 (mover controllers, requests, views, testes)
**Phase 4 inteira com Phase 5**: Podem correr em paralelo após Phase 3
**Phase 5**: T066-T069, T073-T086, T088-T090, T094-T097 (mover ficheiros)
**Phase 7**: T117 e T118 (code-reviewer e security-auditor)

---

## Implementation Strategy

### MVP First (US1 + US2 + US3)

1. Phase 1: Criar infraestrutura modular (Composer path repos + ServiceProviders)
2. Phase 2: Migrar Core (componentes, layouts, dashboard)
3. Phase 3: Migrar User (model, controllers, settings)
4. **STOP e VALIDAR**: App funciona com Core + User modulares

### Incremental Delivery

1. Setup + Core + User → Fundação modular funcional
2. + Auth → Autenticação isolada
3. + Workspace → Feature module desacoplável
4. + Verification → Enforcement automatizado
5. + Polish → Limpeza e review

---

## Notes

- [P] tasks = ficheiros diferentes, sem dependências
- Cada fase termina com `php artisan test --compact`
- Migrations são MOVIDAS, não re-executadas
- Componentes Core: `<x-button>` sem prefixo; Workspace: `<x-workspace::*>`
- Zero dependências externas — tudo com Composer + Laravel nativos
- Ficheiros migrados são removidos da origem em cada fase (incremental)
