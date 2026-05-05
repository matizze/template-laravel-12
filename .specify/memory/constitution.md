<!--
SYNC IMPACT REPORT
==================
Version change: 1.2.1 → 1.3.0 (MINOR: Workspace module replaced by hierarchical Tenant module)
Modified:
  - Core Principles/III — module list (Core/User/Auth/Tenant) and dependency diagram updated
  - Module Boundaries/Workspace section renamed to /Tenant — full inventory rewritten to reflect hierarchical multi-tenancy (parent_id, isOperable, soft-deletes, tenant_user pivot, dual-state onboarding, removal of Invitation per FR-020)
  - Module Boundaries/User — `HasTenants` trait import documented as the canonical extension point
Templates reviewed:
  - .specify/templates/plan-template.md ✅ (no changes required)
  - .specify/templates/spec-template.md ✅ (no changes required)
  - .specify/templates/tasks-template.md ✅ (no changes required)
Specs superseded:
  - 001-workspace-management — replaced by 004-multi-tenant-hierarchy (functional baseline preserved minus invitations)
  - 002-invite-redirect-flow — Invitation flow removed (FR-020)
Deferred TODOs: none
-->

# Laravel 12 Template Constitution

## Core Principles

### I. Test-First Development (NON-NEGOTIABLE)

TDD is mandatory for all feature work. The cycle MUST follow: write tests → confirm they FAIL → implement
until green → refactor. No code MUST be written without a failing test first.

- Use PHPUnit feature tests for HTTP flows, controllers, middleware, and business logic.
- Use Dusk browser tests exclusively for JavaScript-dependent flows (Alpine.js interactions, modals).
- Run tests with `php artisan test --compact` using the minimum filter needed.
- PHPFlasher consumes flash keys — MUST NOT use `assertSessionHas` for `success`, `error`, `warning`, `info`.
- Every PR MUST include tests covering happy path, failure paths, and edge cases.

**Rationale**: Tests written after the fact validate assumptions that are already baked in. Tests written
first drive design toward testable, minimal implementations and catch regressions across module boundaries.

### II. The Laravel Way

All code MUST follow idiomatic Laravel conventions. Artisan generators MUST be used when creating new
classes. Frameworks and ORM capabilities MUST be used before any custom infrastructure.

- MUST use `php artisan make:*` commands to create controllers, models, requests, migrations, etc.
- MUST use Eloquent ORM and relationships; avoid raw `DB::` queries (prefer `Model::query()`).
- MUST use Form Request classes for all validation — never inline validation in controllers.
- MUST use named routes and `route()` for URL generation.
- MUST use `config()` helper — never call `env()` outside of config files.
- MUST use `->with('success'|'error'|'warning'|'info', '...')` for flash messages (PHPFlasher keys).
- MUST run `vendor/bin/pint --dirty --format agent` after any PHP file change.
- SHOULD use queued jobs (`ShouldQueue`) for time-consuming operations.
- SHOULD follow PHP 8.4 features: constructor property promotion, typed properties, match expressions.

**Rationale**: Diverging from Laravel idioms increases onboarding friction and breaks framework tooling
(IDE helpers, route caching, policy auto-discovery, etc.).

### III. Modular Architecture

The codebase MUST be organized into four modules — **Core**, **User**, **Auth**, **Tenant** — each with
clear boundaries. No module MUST depend on a module lower in the dependency chain.

```
Core ← User ← Auth
            ← Tenant
```

Each module owns its models, controllers, requests, views, and tests. Cross-module access MUST go through
defined extension points (dynamic relationship registration via `resolveRelationUsing()`, service classes, events) — never via direct model imports or mutation across modules.

**Rationale**: Separation by domain concern enables independent feature delivery, isolated test suites,
and a codebase that scales without becoming a big ball of mud. The flat dependency tree prevents cycles.

### IV. Simplicity (YAGNI)

Only implement what is required for the current feature. No speculative infrastructure, no premature
abstractions, no backwards-compatibility shims.

- MUST NOT add patterns (repository, service layer, etc.) unless the feature requires them.
- MUST NOT create helpers or utilities for one-time operations.
- MUST NOT introduce backwards-compatibility code for removed functionality.
- Three similar lines of code MUST NOT be abstracted into a premature utility.

**Rationale**: Every abstraction added "just in case" becomes debt. The right amount of complexity is
the minimum needed for the current task.

### V. Reference CLAUDE.md and AGENTS.md

All AI agents and human developers MUST treat `CLAUDE.md` and `AGENTS.md` as runtime development
guidance. These files document stack versions, conventions, tooling, and environment details that
override general defaults.

- Agents MUST read `CLAUDE.md` at the start of any development session.
- Agents MUST use Laravel Boost MCP `search-docs` before making architectural decisions.
- Agents MUST use Herd MCP to discover the correct site URL before running Dusk tests.

**Rationale**: The project uses specific versions of Laravel, Tailwind, Alpine.js, and tooling that
deviate from common defaults. Ignoring these files produces incompatible code.

### VI. MCP Tooling — Serena, Laravel Boost, Herd

Agents MUST actively use the three MCP plugins throughout every development session. Each plugin has
a distinct responsibility; they are not interchangeable.

| Plugin | When to use |
|--------|-------------|
| **Serena** | Symbol-level code navigation — find symbols, read relationships, targeted edits via `find_symbol`, `get_symbols_overview`, `find_referencing_symbols`, `replace_symbol_body` |
| **Laravel Boost** | Laravel-specific intelligence — `search-docs` for documentation, `database-schema`/`database-query` for DB inspection, `last-error` for recent exceptions, `browser-logs` for frontend debugging, `get-absolute-url` for URL generation |
| **Herd** | Local environment discovery — `get_all_sites`/`get_site_information` before any Dusk test or URL sharing, service status, PHP version management |

**Post-implementation gate (NON-NEGOTIABLE)**: After every implementation step is complete and tests
are green, agents MUST invoke the `mcp__plugin_laravel-boost_laravel-boost__laravel-code-simplifier`
tool to review changed code for reuse, quality, and efficiency. This gate runs BEFORE committing or
creating a PR.

**Rationale**: Serena prevents unnecessary full-file reads and enables precise edits at scale. Laravel
Boost grounds every decision in version-accurate documentation. Herd ensures the correct runtime is
always targeted. The simplify gate enforces code quality as a non-optional step, not an afterthought.

## Module Boundaries

### Core — Pure Infrastructure

No business logic. No dependency on other modules.

| Type | Files |
|------|-------|
| Providers | `AppServiceProvider` |
| Views/Components | `components/avatar`, `button`, `card`, `modal`, `pagination`, `nav-item`, `user-menu`, `form/*`, `icon/*`, `layout/*` |
| Views | `dashboard.blade.php` (slot for content injected by other modules) |

### User — Shared Kernel

Base user model + account management + admin user CRUD. Depended on by Auth and Tenant.

| Type | Files |
|------|-------|
| Models | `User` (clean — no tenant methods; `HasTenants` trait imported as documented exception) |
| Controllers | `UserController`, `SettingsController` |
| Requests | `StoreUserRequest`, `UpdateUserRoleRequest`, `UpdateProfileRequest`, `UpdatePasswordRequest`, `DeleteAccountRequest` |
| Commands | `CreateUserCommand` |
| Views | `settings/` |
| Tests | `UserManagementTest`, `SettingsTest`, `CreateUserCommandTest` |

### Auth — Authentication & Password Recovery

Depends on Core + User.

| Type | Files |
|------|-------|
| Controllers | `LoginController`, `RegisterController`, `ForgotPasswordController`, `ResetPasswordController` |
| Requests | `MakeLoginRequest`, `MakeRegisterRequest`, `ForgotPasswordRequest`, `ResetPasswordRequest` |
| Views | `auth/` |
| Routes | `routes/auth.php` |
| Tests | `AuthTest`, `PasswordResetTest` |

### Tenant — Hierarchical Tenant Management, Links & Onboarding

Depends on Core + User. Replaces the former Workspace module (specs 001/002 superseded by 004).

Hierarchical: `tenants` carry an optional `parent_id` self-reference. Operability is derived (a tenant is operable iff it has no children); only operable tenants accept `tenant_user` links and only operable tenants can be active in a session. User is a global entity; access to a tenant is granted exclusively via `tenant_user` records, never via a column on `users`.

| Type | Files |
|------|-------|
| Models | `Tenant` (with `parent`/`children`/`descendants`/`ancestors`/`isOperable`/`path` + `SoftDeletes`), `TenantUser` (pivot, role-bearing) |
| Dynamic Relations | `tenants()`, `ownedTenants()` registered on User via `resolveRelationUsing()` in TenantServiceProvider; `HasTenants` trait imported on User as documented exception |
| Traits | `HasTenants` (utility: `roleIn(Tenant)`, `isMemberOf(Tenant)`) |
| Enum | `TenantRole` (Owner, Admin, Member, Viewer) |
| Policy | `TenantPolicy` |
| Service | `CurrentTenantManager` (registered as `scoped` for Octane safety; validates `isOperable` on `set`) |
| Middleware | `SetCurrentTenant` (just-in-time invalidation: leaf check + active link check on every request) |
| Rules | `ParentHasNoActiveLinks` (FR-017) |
| Controllers | `TenantController`, `TenantSettingsController`, `TenantUserController`, `OnboardingController` (dual-state), `UserCreationController` |
| Requests | `CreateTenantRequest`, `UpdateTenantSettingsRequest`, `DeleteTenantRequest`, `RestoreTenantRequest`, `AttachTenantUserRequest`, `DetachTenantUserRequest`, `UpdateTenantUserRoleRequest`, `TransferOwnershipRequest`, `CreateUserRequest` |
| Views/Components | `dashboard/`, `onboarding.blade.php` (dual state: bootstrap vs no-access), `users/create.blade.php`, `tenant-switcher`, `create-tenant-modal` |
| Tests | `TenantTest`, `TenantHierarchyTest`, `TenantHierarchyPerformanceTest`, `TenantSoftDeleteTest`, `TenantUserAttachTest`, `UserCreationTest`, browser `TenantFlowTest` |
| Removed (FR-020) | Invitation model/migration/factory/notification/modal/request and `InviteTest` — replaced by separate user-creation (US3) and link-attach (US4) flows |

## Agent Assignments

Every workflow step has a designated agent. Agents MUST be invoked at the correct step — not replaced
by the main session doing the same work unassisted.

| Step | Agent | Trigger |
|------|-------|---------|
| **Plan** | `architect` | Invoked by `/speckit.plan` — module placement, pattern selection, dependency boundaries |
| **Tests (TDD gate)** | `testing-expert` | Invoked before any implementation — writes failing PHPUnit/Dusk tests |
| **Models / Migrations** | `eloquent-specialist` | Invoked when creating/modifying Eloquent models, migrations, relationships, scopes |
| **Controllers / Requests** | *(main session)* | Standard implementation following Principles II + VI |
| **Simplify gate** | `mcp__plugin_laravel-boost_laravel-boost__laravel-code-simplifier` | Invoked after every implementation step is green — Laravel Boost MCP tool for code simplification |
| **Debug on failure** | `debugger` | Invoked when a test fails unexpectedly or an error cannot be diagnosed in 2 attempts |
| **Pre-PR review** | `code-reviewer` + `security-auditor` | Invoked in parallel before creating the PR |
| **Architecture change** | `architecture-reviewer` | Invoked whenever a module boundary is crossed or a new cross-module extension point is added |

**Rules:**
- `testing-expert` and `eloquent-specialist` MUST run before the main session writes any implementation.
- After every implementation step is green, agents MUST call
  `mcp__plugin_laravel-boost_laravel-boost__laravel-code-simplifier` — MUST NOT be replaced by the
  `/simplify` skill or any other substitute.
- `code-reviewer` and `security-auditor` MUST run in parallel (not sequentially) to save time.
- `architect` is the gatekeeper for any cross-module dependency. If a task touches two modules,
  `architect` MUST approve the extension point before implementation begins.

## Development Workflow

Every feature MUST follow this order:

1. **Spec** — define user stories and acceptance scenarios (`/speckit.specify`).
2. **Plan** — `architect` designs approach, module placement, constitution check (`/speckit.plan`).
3. **Tasks** — generate ordered task list with TDD gates (`/speckit.tasks`).
4. **Tests first** — `testing-expert` writes failing PHPUnit/Dusk tests per task.
5. **Models** — `eloquent-specialist` creates/updates Eloquent models and migrations.
6. **Implement** — main session makes tests pass, following Laravel conventions.
7. **Simplify** — call `mcp__plugin_laravel-boost_laravel-boost__laravel-code-simplifier` on changed code.
8. **Format** — run `vendor/bin/pint --dirty --format agent`.
9. **Pre-PR** — `code-reviewer` + `security-auditor` run in parallel.
10. **PR** — create PR; respond to all review threads before merging.

No step MUST be skipped. If `architect` flags a module boundary violation, implementation MUST stop
until the design is corrected.

## Governance

This constitution supersedes all other practices and defaults. Amendments MUST be made via the
`/speckit.constitution` command, which increments the version and produces a Sync Impact Report.

- **MAJOR bump**: Removal or incompatible redefinition of a principle or module boundary.
- **MINOR bump**: New principle, module, or materially expanded guidance.
- **PATCH bump**: Clarifications, wording, non-semantic refinements.

All PRs MUST verify compliance with the Constitution Check in `plan.md` before merging. Complexity
violations (e.g., adding a pattern not justified by the current feature) MUST be documented in the
Complexity Tracking table of `plan.md`.

Runtime guidance: `CLAUDE.md` (agent tooling, commands, conventions) and `AGENTS.md` (auth flow,
testing rules, environment).

**Version**: 1.3.0 | **Ratified**: 2026-03-21 | **Last Amended**: 2026-05-05
