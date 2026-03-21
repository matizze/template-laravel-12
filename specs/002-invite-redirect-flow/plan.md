# Implementation Plan: Invite Redirect Flow

**Branch**: `002-invite-redirect-flow` | **Date**: 2026-03-21 | **Spec**: [spec.md](./spec.md)

## Summary

Replace the session-based `invitation_token` mechanism with a redirect URL approach: when a workspace invite is sent, the system detects whether the invitee has an account and generates a link to `/register?redirect=/invitation/{token}` or `/login?redirect=/invitation/{token}`. After successful auth, the redirect param drives the user to the invite acceptance page. The `accept()` action gains first-class support for authenticated users hitting the URL directly.

## Technical Context

**Language/Version**: PHP 8.4 / Laravel 12
**Primary Dependencies**: Laravel Blade, Eloquent ORM, Laravel Notifications (mail)
**Storage**: SQLite (dev) / PostgreSQL (prod) — no schema changes required
**Testing**: PHPUnit 11 (feature tests)
**Target Platform**: Web application (Laravel MVC + Blade)
**Project Type**: Web service
**Performance Goals**: Standard web app — invite email delivered within 60s via queue
**Constraints**: Redirect param must be validated as internal path before use (FR-006)
**Scale/Scope**: Existing user base; no volume changes

## Constitution Check

Constitution is a placeholder template — no active gates defined. Proceeding under project CLAUDE.md rules:

- ✅ No new dependencies introduced
- ✅ No schema changes required
- ✅ Tests required for every change (PHPUnit feature tests)
- ✅ Flash messages use `success`/`error` keys only
- ✅ Form Requests used for validation
- ✅ Laravel Pint formatting applied before finalising

## Project Structure

### Documentation (this feature)

```text
specs/002-invite-redirect-flow/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit.tasks)
```

### Source Code (repository root)

```text
app/
├── Http/
│   └── Controllers/
│       ├── Auth/
│       │   ├── LoginController.php       # Add redirect param handling after login
│       │   └── RegisterController.php   # Add redirect param handling after register
│       └── MemberController.php         # Update invite() to send correct link; update accept() to remove session logic
├── Notifications/
│   └── WorkspaceInviteNotification.php  # Accept link type (register/login) and generate redirect URL

resources/
└── views/
    └── auth/
        ├── login.blade.php              # Preserve redirect param via hidden input
        └── register.blade.php          # Preserve redirect param via hidden input

tests/
└── Feature/
    └── InviteTest.php                  # Update existing + add new redirect flow tests
```

**Structure Decision**: Single Laravel project. All changes are in existing files — no new controllers, models, or migrations required.

## Complexity Tracking

No constitution violations.
