# Tasks: Invite Redirect Flow

**Input**: Design documents from `/specs/002-invite-redirect-flow/`
**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, quickstart.md ✅

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)

---

## Phase 1: Setup (Foundational — no user story label)

- [x] T001 Update `WorkspaceInviteNotification` to accept a `bool $isRegistered` parameter and generate `/login?redirect=/invitation/{token}` for registered invitees and `/register?redirect=/invitation/{token}` for unregistered invitees in `app/Notifications/WorkspaceInviteNotification.php`

---

## Phase 2: User Story 1 — Unregistered User Receives Invite and Registers

**Story goal**: Admin invites an unregistered email → system sends register link with redirect → user registers → lands on invite acceptance page → joins workspace.

**Independent test**: Invite an unregistered email, assert notification sent (on-demand), assert link contains `/register?redirect=`, register via that link, assert joined workspace.

- [x] T002 [US1] Update `MemberController::invite()` to handle both registered and unregistered cases: when invitee has no account use `Notification::route('mail', $email)->notify(...)` with `isRegistered: false`; when invitee has an account notify the `User` model instance with `isRegistered: true`. This single task covers the full `invite()` method to avoid split-edit conflicts in `app/Http/Controllers/MemberController.php`
- [x] T003 [P] [US1] Add `<input type="hidden" name="redirect" value="{{ old('redirect', request('redirect')) }}">` to the register form — using `old('redirect', request('redirect'))` so the param is preserved across validation errors (FR-007) in `resources/views/auth/register.blade.php`
- [x] T004 [US1] Update `RegisterController::store()` to validate the `redirect` request param via a private `isValidRedirect(string $redirect): bool` method (must start with `/` and not `//`), then redirect to it after registration if valid; otherwise redirect to `onboarding` route. Each controller will have its own private copy of this method in `app/Http/Controllers/Auth/RegisterController.php`
- [x] T005 [US1] Write PHPUnit feature tests covering: (a) invite unregistered email sends on-demand notification with register link + redirect param, (b) registering via redirect link joins workspace, (c) registering independently (no redirect) goes to normal onboarding in `tests/Feature/InviteTest.php`

---

## Phase 3: User Story 2 — Registered User Receives Invite via Login Redirect

**Story goal**: Admin invites a registered email → system sends login link with redirect → user logs in → lands on invite acceptance page → joins workspace.

**Independent test**: Invite a registered email, assert notification sent to User model, assert link contains `/login?redirect=`, log in via that link, assert joined workspace.

- [x] T006 [P] [US2] *(MemberController::invite() already updated in T002 — no code changes needed here. This phase focuses on login flow.)* Add `<input type="hidden" name="redirect" value="{{ old('redirect', request('redirect')) }}">` to the login form — using `old('redirect', request('redirect'))` so the param is preserved across validation errors (FR-007) in `resources/views/auth/login.blade.php`
- [x] T007 [US2] Update `LoginController::store()` to validate the `redirect` request param via its own private `isValidRedirect(string $redirect): bool` method (same logic as RegisterController: starts with `/`, not `//`) and redirect to it after successful login if valid; otherwise redirect to `dashboard` in `app/Http/Controllers/Auth/LoginController.php`
- [x] T008 [US2] Write PHPUnit feature tests covering: (a) invite registered email sends notification to User with login link + redirect param, (b) logging in via redirect link joins workspace, (c) logging in with mismatched email shows mismatch error on acceptance page in `tests/Feature/InviteTest.php`

---

## Phase 4: User Story 3 — Direct Invite Link Access by Authenticated User

**Story goal**: Authenticated user visits `/invitation/{token}` directly → if email matches, joined immediately; if mismatch, clear error shown.

**Independent test**: Log in, visit invite URL directly, assert joined workspace without any redirect to login/register.

- [x] T009 [US3] Update `MemberController::accept()` to remove the `session(['invitation_token' => $token])` call and replace the unauthenticated redirect with a smart redirect: if invitee email has an account → `/login?redirect=/invitation/{token}`, else → `/register?redirect=/invitation/{token}`. **Preserve existing expired/invalid token handling** — the `isExpired()` check and the `pending()` scope must remain intact (FR-009) in `app/Http/Controllers/MemberController.php`
- [x] T010 [US3] Write PHPUnit feature tests covering: (a) authenticated user with matching email accepts invite directly, (b) authenticated user with mismatched email sees error, (c) unauthenticated user visiting invite URL is redirected to login or register with correct redirect param, (d) expired invite token shows error without breaking the flow in `tests/Feature/InviteTest.php`

---

## Phase 5: Polish & Cross-Cutting Concerns

- [x] T011 [P] Verify and remove any remaining `session(['invitation_token'])` writes or reads not yet removed by T004/T007/T009 across `LoginController`, `RegisterController`, and `MemberController` in `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Controllers/Auth/RegisterController.php`, `app/Http/Controllers/MemberController.php`
- [x] T012 [P] Update existing `InviteTest.php` tests that assert on `session('invitation_token')` to remove those assertions (session token approach is removed) in `tests/Feature/InviteTest.php`
- [x] T013 [P] Run `vendor/bin/pint --dirty --format agent` to apply code style to all modified PHP files
- [x] T014 Run full test suite `php artisan test --compact` and confirm all tests pass

---

## Dependencies

```
T001 (notification update)
  └── T002 (invite method — both cases) → T005 (US1 tests)
                                        → T008 (US2 tests)

T003 (register hidden input) → T004 (register redirect) → T005 (US1 tests)
T006 (login hidden input)    → T007 (login redirect)    → T008 (US2 tests)

T009 (accept removes session, preserves expired handling) → T010 (US3 tests)

All stories complete → T011 → T012 → T013 → T014 (full suite)
```

## Parallel Execution

Stories US1 and US2 can be worked in parallel after T001 + T002 complete:

- **Stream A**: T003 → T004 → T005
- **Stream B**: T006 → T007 → T008
- **Stream C** (independent): T009 → T010

Polish (T011–T014) runs after all stories complete.

## Implementation Strategy

1. **MVP**: Complete Phase 1 (T001) + Phase 2 (T002–T005) — unregistered invite flow fully working
2. **Increment 2**: Phase 3 (T006–T008) — registered invite flow
3. **Increment 3**: Phase 4 (T009–T010) — direct link access + session token removal
4. **Cleanup**: Phase 5 (T011–T014) — final session cleanup, format, test suite

**Total tasks**: 14
**Per user story**: US1 = 4 (T002–T005), US2 = 4 (T006–T008 + T002 shared), US3 = 2 (T009–T010), Setup = 1 (T001), Polish = 4 (T011–T014)
**Parallel opportunities**: US1 streams (T003/T004) and US2 streams (T006/T007) after T001+T002; T011/T012/T013 in polish phase
