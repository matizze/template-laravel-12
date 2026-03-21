# Research: Invite Redirect Flow

## Redirect Parameter Validation

**Decision**: Use `Str::startsWith($redirect, '/')` combined with a check that the URL does not start with `//` (protocol-relative) to ensure only internal paths are accepted.

**Rationale**: Laravel's `redirect()->intended()` and similar helpers do not validate external URLs. A simple "starts with `/` but not `//`" check is the standard Laravel community approach for open redirect prevention. No package dependency needed.

**Alternatives considered**:
- `parse_url()` + compare host: more robust but overkill for this use case
- `URL::isValidUrl()`: validates format, not origin

---

## Preserving Redirect Param Across Form Validation Errors

**Decision**: Add a `<input type="hidden" name="redirect" value="{{ request('redirect') }}">` in the login and register Blade forms. On validation failure, `back()->withInput()` preserves the POST body, so the hidden input value is repopulated via `old('redirect')`.

**Rationale**: This is the standard Laravel pattern — no session storage needed. The value travels through the form round-trip automatically.

---

## Notification: Registered vs Unregistered Invitee

**Decision**: Update `MemberController::invite()` to check if the invitee has an account. Pass a boolean or the invitee's registration status to `WorkspaceInviteNotification`, which generates either a `/login?redirect=...` or `/register?redirect=...` link.

**Rationale**: The notification already generates the `$acceptUrl`. Extending it to accept a `bool $isRegistered` parameter keeps all URL generation inside the notification class. Unregistered invitees use `Notification::route('mail', $email)->notify(...)` (on-demand notification).

**Alternatives considered**:
- Two separate notification classes: unnecessary duplication
- URL generation in the controller: violates single-responsibility

---

## Session Token Removal (FR-008)

**Decision**: Remove all `session(['invitation_token' => $token])` writes from `MemberController::accept()` and all `session()->get('invitation_token')` reads from `LoginController::store()` and `RegisterController::store()`.

**Rationale**: The redirect URL approach makes the session token obsolete. The token is embedded in the URL itself, so it survives logout, browser restart, and email-mismatch scenarios.

---

## accept() for Unauthenticated Users

**Decision**: When `MemberController::accept()` is hit without an authenticated user, redirect to login or register (based on whether the invitee's email has an account) with the invite URL as the redirect parameter — instead of saving to session.

**Rationale**: This makes the flow stateless. The invite URL `/invitation/{token}` becomes the redirect target, so after auth the user lands back on `accept()` which then completes the join.
