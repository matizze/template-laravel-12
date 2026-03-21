# Implementation Plan: Modular Monolith Migration

**Branch**: `003-modular-migration` | **Date**: 2026-03-21 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/003-modular-migration/spec.md`

## Summary

Migrar o monolito Laravel actual para uma arquitectura modular sem dependências externas. Cada módulo é um Composer path repository com o seu `composer.json`, auto-descoberto via Laravel package discovery. O projecto será dividido em 4 módulos — Core, User, Auth, Workspace — com regras de dependência claras (Core ← User ← Auth, Workspace). Componentes Core são registados globalmente sem prefixo. O módulo Workspace deve ser removível sem quebrar o resto.

## Technical Context

**Language/Version**: PHP 8.4 / Laravel 13
**Primary Dependencies**: Nenhuma adicional — Composer path repositories + Laravel package discovery nativos
**Storage**: SQLite (dev), PostgreSQL (prod) — sem alterações de schema
**Testing**: PHPUnit 11 via `php artisan test`, Dusk para browser tests
**Target Platform**: Linux server (Docker + Octane/Swoole)
**Project Type**: Web application (monolito modular)
**Performance Goals**: Tempo de boot não deve aumentar >10% após modularização
**Constraints**: Zero breaking changes; zero dependências externas para modularização; todos os testes devem continuar a passar
**Scale/Scope**: 4 módulos, ~40 ficheiros PHP a migrar, ~30 views Blade, ~20 testes

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Check | Status | Notes |
|-------|--------|-------|
| **I. TDD** — Tests written before implementation? | ✅ | Refactor — testes existentes servem como safety net. Novos testes apenas para enforcement (US6). |
| **II. Laravel Way** — Using Artisan generators, Eloquent, Form Requests, named routes? | ✅ | Sem mudanças em Eloquent/Forms/Routes. ServiceProviders usam API nativa do Laravel. |
| **III. Modular Architecture** — Code placed in correct module (Core/User/Auth/Workspace)? | ✅ | Mapeamento definido na spec e constitution. Enforcement via CI + PHPStan. |
| **IV. Simplicity** — No premature abstractions, speculative patterns, or backwards-compat shims? | ✅ | Mover ficheiros + actualizar namespaces. Sem novos padrões. Zero dependências externas. |
| **V. CLAUDE.md / AGENTS.md** — Laravel Boost `search-docs` consulted before implementation? | ✅ | Documentação consultada para service providers, Blade components, package discovery. |
| **VI. MCP Tooling** — Serena for navigation, Laravel Boost for docs/DB, Herd for site URL? | ✅ | Serena para navegar símbolos. Boost para docs. Herd para Dusk. |
| **Agent Assignments** — `architect` approved module placement? `testing-expert` scheduled for TDD gate? | ✅ | Mapeamento validado via brainstorm. `testing-expert` scheduled para testes de enforcement (US6). |

> No violations detected.

## Project Structure

### Documentation (this feature)

```text
specs/003-modular-migration/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Phase 0: research findings
├── data-model.md        # Phase 1: entity → module mapping
├── quickstart.md        # Phase 1: setup guide
├── checklists/
│   └── requirements.md  # Spec quality checklist
└── tasks.md             # Phase 2 output (/speckit.tasks command)
```

### Source Code (repository root)

```text
app-modules/
├── core/
│   ├── composer.json                    # PSR-4: Modules\Core\, Laravel provider discovery
│   ├── src/
│   │   └── Providers/
│   │       └── CoreServiceProvider.php  # Loads components globally (no prefix), views, routes
│   ├── resources/
│   │   ├── views/
│   │   │   └── dashboard.blade.php
│   │   └── components/                  # Registered globally via Blade::anonymousComponentPath()
│   │       ├── avatar.blade.php
│   │       ├── button.blade.php
│   │       ├── card.blade.php
│   │       ├── modal.blade.php
│   │       ├── pagination.blade.php
│   │       ├── nav-item.blade.php
│   │       ├── user-menu.blade.php
│   │       ├── form/
│   │       ├── icon/
│   │       └── layout/
│   ├── routes/
│   ├── database/
│   └── tests/
│
├── user/
│   ├── composer.json                    # PSR-4: Modules\User\, requires modules/core
│   ├── src/
│   │   ├── Models/User.php
│   │   ├── Http/Controllers/
│   │   ├── Http/Requests/
│   │   ├── Console/Commands/
│   │   └── Providers/UserServiceProvider.php
│   ├── resources/views/settings/
│   ├── routes/web.php
│   ├── database/migrations/
│   └── tests/
│
├── auth/
│   ├── composer.json                    # PSR-4: Modules\Auth\, requires modules/core, modules/user
│   ├── src/
│   │   ├── Http/Controllers/
│   │   ├── Http/Requests/
│   │   └── Providers/AuthServiceProvider.php
│   ├── resources/views/auth/
│   ├── routes/auth.php
│   ├── database/
│   └── tests/
│
└── workspace/
    ├── composer.json                    # PSR-4: Modules\Workspace\, requires modules/core, modules/user
    ├── src/
    │   ├── Models/
    │   ├── Enums/
    │   ├── Traits/HasWorkspaces.php
    │   ├── Policies/
    │   ├── Services/
    │   ├── Http/Controllers/
    │   ├── Http/Middleware/
    │   ├── Http/Requests/
    │   ├── Notifications/
    │   └── Providers/WorkspaceServiceProvider.php
    ├── resources/
    │   ├── views/
    │   └── components/                  # Registered with prefix: <x-workspace::*>
    ├── routes/web.php
    ├── database/migrations/
    └── tests/
```

### Root composer.json changes

```json
{
    "repositories": [
        {"type": "path", "url": "app-modules/core"},
        {"type": "path", "url": "app-modules/user"},
        {"type": "path", "url": "app-modules/auth"},
        {"type": "path", "url": "app-modules/workspace"}
    ],
    "require": {
        "modules/core": "*@dev",
        "modules/user": "*@dev",
        "modules/auth": "*@dev",
        "modules/workspace": "*@dev"
    }
}
```

### Module composer.json example (core)

```json
{
    "name": "modules/core",
    "description": "Core module — infrastructure and shared components",
    "autoload": {
        "psr-4": {
            "Modules\\Core\\": "src/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Modules\\Core\\Providers\\CoreServiceProvider"
            ]
        }
    }
}
```

**Structure Decision**: Modular monolith sem dependências externas. Cada módulo em `app-modules/` é um Composer path repository com PSR-4 autoloading e Laravel package discovery. O directório `app/` raiz mantém apenas ficheiros que o Laravel exige (ex: `Controller.php` base).

## Complexity Tracking

> No violations detected. All checks pass.

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| *(nenhuma)* | — | — |
