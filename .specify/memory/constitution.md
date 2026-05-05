<!--
SYNC IMPACT REPORT
==================
Version change: 1.3.0 → 1.4.0 (MINOR: Modular Architecture principle abstracted; module enumeration removed from constitution)
Modified:
  - Core Principles/III "Modular Architecture" — rewritten to describe modular architecture as a principle (boundaries, dependency rules, cross-module extension patterns) without naming concrete modules. Adding/renaming/removing a module is now an ordinary development operation that does NOT require a constitutional amendment.
  - Section "Module Boundaries" — REMOVED. The current list of modules (currently includes Core, User, Auth, Tenant) and their responsibilities now lives in `AGENTS.md` / `README.md` as living documentation, updated as part of normal development work.
Carried over from 1.3.0 (Tenant module nominal version superseded by this bump):
  - 001-workspace-management — replaced by 004-multi-tenant-hierarchy (functional baseline preserved minus invitations)
  - 002-invite-redirect-flow — Invitation flow removed (FR-020)
Templates reviewed:
  - .specify/templates/spec-template.md ✅ (no changes required)
  - .specify/templates/plan-template.md ⚠ (Constitution Check row III references nominal modules — should be generalized in a follow-up edit)
  - .specify/templates/tasks-template.md ⚠ (Path Conventions section enumerates nominal module paths — should be generalized in a follow-up edit)
Deferred TODOs:
  - Align plan-template.md and tasks-template.md with the abstracted Principle III (remove nominal module references; replace with pointer to AGENTS.md/README.md).
  - Migrate the Tenant module description previously held in constitution Module Boundaries to AGENTS.md/README.md as living documentation.
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

The codebase MUST be organized into modules — self-contained units of code grouped by domain
responsibility. Each module owns its models, controllers, requests, views, routes, and tests, and
exposes a narrow public surface to the rest of the application.

**Dependency rules:**

- The dependency graph between modules MUST be acyclic. A module MUST NOT depend, directly or
  transitively, on a module that depends on it.
- Exactly one module SHOULD act as the **shared kernel** — the lowest layer in the chain, containing
  base infrastructure (UI components, layouts, root service providers) with no business logic. Every
  other module MAY depend on it.
- One module MAY act as the **identity / account base** that other domain modules build on (for
  example, the module that owns the base user model). Domain modules MAY depend on it.
- Domain modules MUST NOT depend on each other directly. When two domain modules need to interact,
  the interaction MUST go through a defined extension point — never through a direct model import,
  trait import, or static call across module boundaries.

**Cross-module extension points (REQUIRED patterns):**

- **Eloquent relations** — When module B needs to expose a relationship on a model owned by module A,
  module B MUST register the relation dynamically via `resolveRelationUsing()` from its own service
  provider. Module A MUST NOT import models, traits, or enums from module B.
- **Services and contracts** — When one module needs behaviour from another, the consumer MUST depend
  on an interface (contract) resolved from the container. The provider module MUST bind a concrete
  implementation in its service provider. No direct concrete-class imports across modules.
- **Events** — Asynchronous, fan-out, or fire-and-forget interactions MUST use Laravel events and
  listeners. The dispatcher MUST NOT know which modules listen.
- **Routes and views** — A module MAY register its own routes and views from its service provider.
  Modules MUST NOT directly include views from another module by absolute path; they MUST go through
  named view namespaces or Blade components exposed by the shared kernel.

**Module lifecycle:**

Adding, splitting, renaming, or removing a module is an ordinary development operation. It MUST NOT
require a constitutional amendment, provided the change respects the dependency rules and extension
points defined above. The current inventory of modules and their responsibilities is living
documentation maintained in `AGENTS.md` and the project README — not in this constitution.

**Rationale**: Separation by domain concern enables independent feature delivery, isolated test
suites, and a codebase that scales without becoming a big ball of mud. Encoding the *rules* of
modularity rather than the *list* of modules keeps the constitution stable as the application grows.

## Module Inventory

The current list of modules, their responsibilities, and the files they own is documented in
`AGENTS.md` (and mirrored in the project README). That documentation is updated as part of normal
development work whenever a module is added, renamed, or restructured.

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

**Version**: 1.4.0 | **Ratified**: 2026-03-21 | **Last Amended**: 2026-05-05
