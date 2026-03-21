# Feature Specification: Modular Monolith Migration

**Feature Branch**: `003-modular-migration`
**Created**: 2026-03-21
**Status**: Draft
**Input**: User description: "Migração do sistema monolito Laravel atual para arquitetura modular sem dependências externas, usando Composer path repositories e Laravel package discovery"

## Clarifications

### Session 2026-03-21

- Q: Qual namespace base para os módulos? → A: `Modules\Core\`, `Modules\User\`, `Modules\Auth\`, `Modules\Workspace\`.
- Q: Componentes Blade do Core precisam de prefixo após migração? → A: Não — Core regista componentes globalmente sem prefixo (`<x-button>`, `<x-card>`), zero mudanças nas views.
- Q: Testes ficam na raiz ou dentro de cada módulo? → A: Dentro de cada módulo (`app-modules/core/tests/`, `app-modules/auth/tests/`, etc.).
- Q: Como garantir que as regras de dependência entre módulos não são violadas? → A: Script CI que verifica `use` statements + regra PHPStan customizada que falha o build em violações.
- Q: Usar InterNACHI/modular ou implementar sem lib? → A: Sem lib — usar Composer path repositories + Laravel package discovery nativos. Zero dependências externas para a modularização.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Criar infraestrutura modular (Priority: P1)

Como desenvolvedor do template, quero criar a infraestrutura modular usando funcionalidades nativas do Composer e Laravel, para que módulos sejam auto-descobertos e carregados sem dependências externas.

**Why this priority**: Sem a infraestrutura modular funcional, nenhum módulo pode ser criado. É o pré-requisito de tudo.

**Independent Test**: Cada módulo tem um `composer.json` registado como path repository no root. O ServiceProvider de cada módulo é auto-descoberto via Laravel package discovery. `composer dump-autoload` resolve os namespaces. Todos os testes existentes continuam a passar.

**Acceptance Scenarios**:

1. **Given** o projecto actual sem modularização, **When** crio a estrutura `app-modules/core/` com `composer.json` e ServiceProvider, **Then** o módulo é auto-descoberto pelo Laravel e o ServiceProvider é registado.
2. **Given** os 4 módulos criados, **When** executo `composer dump-autoload`, **Then** todos os namespaces `Modules\*` são resolvidos correctamente.
3. **Given** os módulos registados, **When** a aplicação é carregada, **Then** os ServiceProviders dos 4 módulos são automaticamente executados.

---

### User Story 2 - Migrar o módulo Core (Priority: P1)

Como desenvolvedor, quero extrair a infraestrutura base (AppServiceProvider, componentes Blade genéricos, layouts, dashboard) para o módulo Core, para que sirva de fundação partilhada por todos os outros módulos.

**Why this priority**: Core é a fundação — todos os módulos dependem dele. Deve ser migrado primeiro.

**Independent Test**: A aplicação arranca. Layouts renderizam. Componentes `<x-button>`, `<x-card>`, etc. funcionam sem prefixo. Todos os testes passam.

**Acceptance Scenarios**:

1. **Given** o módulo Core criado, **When** o AppServiceProvider é movido para o módulo, **Then** continua a ser registado e executado normalmente.
2. **Given** o módulo Core, **When** componentes Blade genéricos (button, card, modal, avatar, pagination, nav-item, user-menu, form/*, icon/*, layout/*) são movidos, **Then** todas as views que os usam continuam a renderizar correctamente sem prefixo (ex: `<x-button>`).
3. **Given** o módulo Core, **When** o dashboard.blade.php é movido com suporte a slots, **Then** outros módulos podem injectar conteúdo no dashboard sem que Core dependa deles.

---

### User Story 3 - Migrar o módulo User (Priority: P1)

Como desenvolvedor, quero extrair a gestão de utilizadores (User model, UserController, SettingsController, CreateUserCommand, requests, views de settings) para o módulo User, para que funcione como Shared Kernel de utilizador.

**Why this priority**: O modelo User é usado por Auth e Workspace. Deve estar migrado antes desses módulos.

**Independent Test**: CRUD de utilizadores funciona. Settings de perfil/password funcionam. `php artisan create:user` funciona. Testes em `app-modules/user/tests/` passam.

**Acceptance Scenarios**:

1. **Given** o módulo User criado, **When** o User model (limpo, sem métodos de workspace) é movido para `Modules\User\Models\User`, **Then** a autenticação e todas as referências ao User continuam a funcionar.
2. **Given** o módulo User, **When** o SettingsController e views de settings são movidos, **Then** as páginas de perfil e segurança funcionam normalmente.
3. **Given** o módulo User, **When** o UserController, StoreUserRequest, UpdateUserRoleRequest são movidos, **Then** a gestão de utilizadores (admin) funciona correctamente.
4. **Given** o módulo User, **When** o CreateUserCommand é movido, **Then** o comando `php artisan create:user` funciona normalmente.

---

### User Story 4 - Migrar o módulo Auth (Priority: P2)

Como desenvolvedor, quero extrair a autenticação (LoginController, RegisterController, ForgotPasswordController, ResetPasswordController, requests, views auth/) para o módulo Auth, para isolar toda a lógica de autenticação.

**Why this priority**: Auth depende de Core e User (já migrados). É um domínio bem delimitado e auto-contido.

**Independent Test**: Login, registo, forgot/reset password funcionam. Testes em `app-modules/auth/tests/` passam.

**Acceptance Scenarios**:

1. **Given** o módulo Auth criado, **When** os controllers de autenticação são movidos, **Then** login, registo e recuperação de password funcionam normalmente.
2. **Given** o módulo Auth, **When** as rotas de auth.php são movidas para o módulo, **Then** todas as URLs de autenticação continuam a responder correctamente.
3. **Given** o módulo Auth, **When** as views auth/ são movidas, **Then** as páginas de login, registo e reset password renderizam correctamente.
4. **Given** o módulo Auth, **When** as form requests (MakeLoginRequest, MakeRegisterRequest, etc.) são movidas, **Then** a validação funciona como antes.

---

### User Story 5 - Migrar o módulo Workspace (Priority: P2)

Como desenvolvedor, quero extrair toda a lógica de workspaces (models, controllers, requests, policies, traits, middleware, notifications, views) para o módulo Workspace, para que possa ser facilmente removido do template.

**Why this priority**: Workspace é o módulo de feature mais complexo. Depende de Core e User. Deve ser o último a migrar.

**Independent Test**: Criação de workspaces, gestão de membros, convites, onboarding e settings de workspace funcionam. Testes em `app-modules/workspace/tests/` passam.

**Acceptance Scenarios**:

1. **Given** o módulo Workspace criado, **When** os models (Workspace, Member, Invitation), enum (WorkspaceRole), policy (WorkspacePolicy) são movidos, **Then** as relações e autorização funcionam normalmente.
2. **Given** o módulo Workspace, **When** a trait HasWorkspaces é criada e adicionada ao User model, **Then** os métodos workspaces(), roleIn() e isMemberOf() funcionam via trait em vez de estarem directamente no User.
3. **Given** o módulo Workspace, **When** o middleware SetCurrentWorkspace e o service CurrentWorkspaceManager são movidos, **Then** a detecção de workspace actual funciona normalmente.
4. **Given** o módulo Workspace, **When** os componentes específicos (workspace-switcher, create-workspace-modal, invite-modal) são movidos com prefixo `<x-workspace::*>`, **Then** renderizam correctamente nas views.
5. **Given** o módulo Workspace, **When** todas as views de dashboard/ e onboarding são movidas, **Then** as páginas de workspace settings, membros e onboarding funcionam.

---

### User Story 6 - Desacoplamento verificável (Priority: P3)

Como desenvolvedor que vai usar este template noutro projecto, quero poder remover o módulo Workspace sem quebrar o resto da aplicação, para ter um template flexível.

**Why this priority**: Valida a qualidade da modularização. Sem esta verificação, a separação pode ser superficial.

**Independent Test**: Remover directório do módulo Workspace, remover `use HasWorkspaces` do User model, remover entry do root `composer.json`, e verificar que Core, User e Auth continuam a funcionar.

**Acceptance Scenarios**:

1. **Given** todos os módulos migrados, **When** o directório do módulo Workspace é removido, a trait HasWorkspaces é removida do User, e a entry do composer.json é removida, **Then** a aplicação arranca sem erros.
2. **Given** o módulo Workspace removido, **When** acedo a rotas de auth e settings, **Then** login, registo e gestão de perfil funcionam normalmente.
3. **Given** o módulo Workspace removido, **When** executo os testes dos módulos Core, User e Auth, **Then** todos passam sem falhas.

---

### Edge Cases

- O que acontece quando dois módulos registam rotas com o mesmo nome? O sistema deve detectar conflitos de nomes de rotas no boot.
- Como lidar com migrations que já foram executadas no monolito e agora vivem num módulo? As migrations devem ser movidas sem re-executar (manter o histórico).
- O que acontece se um módulo é removido mas ainda há dados das suas tabelas na base de dados? As tabelas permanecem — a remoção do módulo é apenas a nível de código.
- Como o dashboard.blade.php em Core lida quando nenhum módulo injecta conteúdo? Deve mostrar uma mensagem de boas-vindas padrão.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A modularização DEVE ser implementada sem dependências externas — usando apenas Composer path repositories e Laravel package discovery nativos.
- **FR-002**: O sistema DEVE suportar 4 módulos: Core, User, Auth, Workspace — cada um com a sua estrutura de directórios (src/, routes/, resources/, tests/, database/) e o seu próprio `composer.json`.
- **FR-003**: Cada módulo DEVE ter o seu próprio ServiceProvider, auto-descoberto via `extra.laravel.providers` no `composer.json` do módulo.
- **FR-004**: O módulo Core NÃO DEVE ter dependências de nenhum outro módulo.
- **FR-005**: O módulo User DEVE depender apenas de Core.
- **FR-006**: O módulo Auth DEVE depender apenas de Core e User.
- **FR-007**: O módulo Workspace DEVE depender apenas de Core e User.
- **FR-008**: O User model DEVE residir no módulo User (`Modules\User\Models\User`), sem métodos de workspace — estes são adicionados via trait HasWorkspaces do módulo Workspace.
- **FR-009**: O dashboard.blade.php em Core DEVE usar slots/sections para que módulos injectem conteúdo sem criar dependências.
- **FR-010**: Todos os testes existentes DEVEM continuar a passar após cada migração de módulo.
- **FR-011**: O módulo Workspace DEVE ser removível sem afectar o funcionamento dos outros módulos (Core, User, Auth).
- **FR-012**: Cada módulo DEVE ser registado como Composer path repository no root `composer.json`, com PSR-4 autoloading no `composer.json` do módulo.
- **FR-013**: As migrations existentes DEVEM ser movidas para os módulos sem necessidade de re-execução. O ServiceProvider de cada módulo carrega as migrations via `$this->loadMigrationsFrom()`.
- **FR-014**: Componentes Blade específicos de um módulo DEVEM residir nesse módulo com prefixo de namespace (ex: `<x-workspace::switcher>`).
- **FR-015**: Componentes Blade genéricos (button, card, modal, form inputs, layouts, etc.) DEVEM residir no módulo Core e ser registados globalmente sem prefixo (ex: `<x-button>`) via `Blade::anonymousComponentPath()`.
- **FR-016**: Os módulos DEVEM usar o namespace `Modules\Core\`, `Modules\User\`, `Modules\Auth\`, `Modules\Workspace\`.
- **FR-017**: Cada módulo DEVE ter os seus testes dentro do próprio directório (`app-modules/{module}/tests/`). O `phpunit.xml` deve incluir test suites para cada módulo.
- **FR-018**: As regras de dependência entre módulos DEVEM ser verificadas automaticamente via script CI que analisa `use` statements e via regra PHPStan customizada que falha o build em violações.

### Key Entities

- **Module**: Unidade organizacional que agrupa código por domínio. Contém `composer.json`, src/, routes/, resources/, tests/, database/. Tem um ServiceProvider próprio auto-descoberto via Laravel package discovery. Namespace: `Modules\{Name}\`.
- **User (model)**: Entidade central partilhada. Reside no módulo User (`Modules\User\Models\User`). Extensível via traits de outros módulos.
- **HasWorkspaces (trait)**: Extensão do User model fornecida pelo módulo Workspace. Adiciona relações e métodos de workspace ao User.
- **Dependency Rule**: Regra de dependência entre módulos — Core ← User ← Auth, Workspace. Nenhum módulo pode depender de um módulo no mesmo nível ou inferior. Enforcement via script CI + PHPStan.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% dos testes existentes passam após a migração completa.
- **SC-002**: Cada módulo pode ser testado isoladamente executando apenas os seus testes em `app-modules/{module}/tests/`.
- **SC-003**: A remoção do módulo Workspace (directório + trait + composer entry) não causa erros nos módulos restantes.
- **SC-004**: O tempo de arranque da aplicação não aumenta mais de 10% após a modularização.
- **SC-005**: Zero dependências externas adicionadas para a modularização — apenas Composer e Laravel nativos.
- **SC-006**: A estrutura de dependências é verificável: script CI e PHPStan confirmam que nenhum `use`/`import` viola as regras de dependência.
- **SC-007**: Zero mudanças em referências a componentes Blade genéricos do Core — `<x-button>` continua a funcionar sem prefixo.

## Assumptions

- O projecto usa Laravel 13 com a estrutura simplificada (sem Kernel.php).
- Composer path repositories e Laravel package discovery são funcionalidades estáveis e retrocompatíveis.
- As migrations existentes já foram executadas no ambiente de desenvolvimento e serão apenas movidas (não re-executadas).
- O módulo de gestão de utilizadores admin será eventualmente separado do SettingsController, mas por agora fica junto com um TODO comment.
- O AuthorizationServiceProvider será deprecado a favor do padrão Laravel (auto-discovery de Policies). Esta mudança será feita durante a migração.
- Componentes Blade de módulos de feature usam namespacing com prefixo (ex: `<x-workspace::switcher>`). Componentes do Core são registados globalmente sem prefixo.
- Cada módulo é um Composer package com o seu próprio `composer.json` definindo PSR-4 autoloading e Laravel provider discovery.
