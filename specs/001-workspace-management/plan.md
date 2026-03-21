# Implementation Plan: Workspace Management

**Branch**: `001-workspace-management` | **Date**: 2026-03-21 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/001-workspace-management/spec.md`

**Note**: This template is filled in by `/speckit.plan` command. See `.specify/templates/plan-template.md` for the execution workflow.

## Summary

Implement a multi-workspace system for Laravel 12, enabling users to create and switch between isolated workspaces, invite team members with role-based permissions, and scope all data by the current workspace context. This feature follows strict TDD discipline with comprehensive feature tests and uses Laravel conventions (Eloquent, Policies, Gates, Form Requests) for all implementation.

## Technical Context

**Language/Version**: PHP 8.4.1+
**Primary Dependencies**: Laravel 12, Eloquent ORM, Laravel Policies & Gates, PHPFlasher with Noty, Alpine.js v3, Tailwind CSS v4, Blade-Lucide-Icons, Laravel Dusk
**Storage**: SQLite (development/testing), PostgreSQL (production)
**Testing**: PHPUnit (feature tests), Laravel Dusk (browser tests for critical flows)
**Target Platform**: Web application (browser-based)
**Project Type**: Web service (backend + frontend with Blade)
**Performance Goals**: Workspace context switching <1 second, invitation email delivery <10 seconds
**Constraints**: Session-based authentication, Docker + Octane deployment support
**Scale/Scope**: No explicit limits defined (use reasonable defaults: ~10 workspaces/user, ~50 members/workspace)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Pre-Design Assessment

| Constitution Principle | Assessment | Status |
|---------------------|-------------|---------|
| **I. Laravel Way First** | Feature uses Eloquent, Policies, Gates, Form Requests, artisan commands | ✅ PASS |
| **II. Convention Over Configuration** | Follows Laravel 12 structure, kebab-case components, REST controllers | ✅ PASS |
| **III. TDD (NON-NEGOTIABLE)** | RED → GREEN → REFACTOR cycle required, tests before implementation | ✅ PASS |
| **IV. Type Safety & Modern PHP** | PHP 8.4.1+, explicit return types, constructor property promotion | ✅ PASS |
| **V. Production-Ready by Default** | Authorization via Policies, rate limiting, migrations reversible | ✅ PASS |
| **VI. Component-Based UI** | Blade components + Tailwind v4, Alpine.js for interactivity | ✅ PASS |
| **VII. Code Quality Gates** | Pint, PHPStan, tests must pass before merge | ✅ PASS |

### Post-Design Assessment

| Constitution Principle | Assessment | Status |
|---------------------|-------------|---------|
| **I. Laravel Way First** | Uses Eloquent relationships, Policies for authorization, Form Requests for validation, artisan commands for scaffolding | ✅ PASS |
| **II. Convention Over Configuration** | Laravel 12 directory structure followed, kebab-case Blade components, RESTful controllers with dot notation routes | ✅ PASS |
| **III. TDD (NON-NEGOTIABLE)** | Research documents TDD workflow, data model includes test considerations, quickstart includes test examples | ✅ PASS |
| **IV. Type Safety & Modern PHP** | Uses PHP 8.4.1+ features ( enums, typed properties, match expressions suggested) | ✅ PASS |
| **V. Production-Ready by Default** | Includes CSRF protection, rate limiting, foreign key constraints, cascade deletes, migration rollback strategy documented | ✅ PASS |
| **VI. Component-Based UI** | Blade components with kebab-case names, Tailwind v4 utilities, Alpine.js directives | ✅ PASS |
| **VII. Code Quality Gates** | Pint, PHPStan, and tests mentioned in quickstart, quality gates documented | ✅ PASS |

**Overall Constitution Compliance**: ✅ **ALL PRINCIPLES SATISFIED**

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)
<!--
  ACTION REQUIRED: Replace the placeholder tree below with a concrete layout
  for this feature. Delete unused options and expand the chosen structure with
  real paths (e.g., apps/admin, packages/something). The delivered plan must
  not include Option labels.
-->

```text
app/
├── Models/
│   ├── Workspace.php
│   ├── Member.php
│   ├── Invitation.php
│   └── User.php (extended)
├── Http/
│   ├── Controllers/
│   │   ├── WorkspaceController.php
│   │   ├── MemberController.php
│   │   ├── SettingsController.php
│   │   └── ProfileController.php
│   ├── Middleware/
│   │   └── SetCurrentWorkspace.php
│   ├── Requests/
│   │   ├── CreateWorkspaceRequest.php
│   │   ├── UpdateSettingsRequest.php
│   │   ├── InviteMemberRequest.php
│   │   └── TransferOwnershipRequest.php
│   └── Notifications/
│       └── WorkspaceInviteNotification.php
├── Policies/
│   ├── WorkspacePolicy.php
│   └── ProjectPolicy.php
└── Traits/
    └── BelongsToWorkspace.php

database/
├── factories/
│   ├── WorkspaceFactory.php
│   ├── MemberFactory.php
│   └── InvitationFactory.php
├── migrations/
│   ├── create_workspaces_table.php
│   ├── create_members_table.php
│   ├── create_invitations_table.php
│   └── add_workspace_id_to_projects_table.php

resources/
├── views/
│   ├── components/
│   │   ├── workspace-switcher.blade.php
│   │   ├── create-workspace-modal.blade.php
│   │   └── invite-modal.blade.php
│   ├── dashboard/
│   │   ├── workspaces/
│   │   │   └── create.blade.php
│   │   ├── settings/
│   │   │   └── index.blade.php
│   │   │   └── partials/
│   │   │       ├── geral.blade.php
│   │   │       └── membros.blade.php
│   │   └── members/
│   │       └── index.blade.php
│   └── profile/
│       └── partials/
│           └── perfil.blade.php

tests/
├── Feature/
│   ├── WorkspaceTest.php
│   ├── InviteTest.php
│   ├── DeleteAccountTest.php
│   ├── ProjectTest.php
│   └── BriefingTest.php
└── Browser/
    ├── LoginFlowTest.php
    ├── RegisterFlowTest.php
    ├── ForgotPasswordFlowTest.php
    └── UserManagementFlowTest.php
```

**Structure Decision**: This is a monolithic Laravel 12 web application following the framework's conventions. Backend (models, controllers, policies) and frontend (Blade views, components) are integrated in a single codebase. No separate frontend/backend splitting is needed as this follows Laravel's opinionated structure.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No violations detected. All constitution principles are aligned with the feature requirements.
