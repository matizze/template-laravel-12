# Feature Specification: Invite Redirect Flow

**Feature Branch**: `002-invite-redirect-flow`
**Created**: 2026-03-21
**Status**: Draft
**Input**: User description: "Melhorar o fluxo de convite de membros do workspace para suportar convidar e-mails não cadastrados"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Unregistered User Receives Invite and Registers (Priority: P1)

An admin invites an e-mail address that has no account. The invitee receives an e-mail with a link pointing to the registration page. After registering, the invitee is automatically redirected to accept the workspace invite without any extra steps.

**Why this priority**: This is the core gap — unregistered users currently never receive any notification and cannot join the workspace without manually navigating to the invite link after registering.

**Independent Test**: An admin invites an unregistered e-mail, the system sends a registration link with embedded invite redirect, the invitee registers and lands on the invite acceptance page.

**Acceptance Scenarios**:

1. **Given** an admin invites `new@example.com` which has no account, **When** the invite is submitted, **Then** an e-mail is sent to `new@example.com` with a link to the registration page that includes the invite acceptance URL as a redirect parameter.
2. **Given** `new@example.com` receives the invite e-mail and clicks the link, **When** they complete registration, **Then** they are automatically redirected to the invite acceptance page and added to the workspace.
3. **Given** `new@example.com` receives the invite e-mail but ignores it, **When** they later register independently, **Then** they are taken to the normal onboarding flow (no forced redirect without the link).

---

### User Story 2 - Registered User Receives Invite via Login Redirect (Priority: P2)

An admin invites an e-mail address that already has an account. The invitee receives an e-mail with a link pointing to the login page with the invite acceptance URL as a redirect parameter. After logging in, they are taken directly to accept the invite.

**Why this priority**: Registered users already had a partial solution via session token, but it was fragile. This story hardens that flow and aligns it with the redirect-based approach for consistency.

**Independent Test**: An admin invites a registered e-mail, the system sends a login link with embedded invite redirect, the invitee logs in and lands on the invite acceptance page.

**Acceptance Scenarios**:

1. **Given** an admin invites `existing@example.com` which already has an account, **When** the invite is submitted, **Then** an e-mail is sent with a link to the login page that includes the invite acceptance URL as a redirect parameter.
2. **Given** `existing@example.com` clicks the link and logs in successfully, **When** authentication succeeds, **Then** they are redirected to the invite acceptance page and added to the workspace.
3. **Given** `existing@example.com` clicks the link but logs in with a different account, **When** the invite acceptance page validates the e-mail, **Then** a clear error is shown explaining the invite was sent to a different address.

---

### User Story 3 - Direct Invite Link Access by Authenticated User (Priority: P3)

A user accesses the invite link directly (e.g. from the original e-mail, while already logged in). The system handles the case without requiring re-authentication.

**Why this priority**: Users may revisit the direct invite link after already being logged in. The system should handle authenticated access gracefully without unnecessary redirects.

**Independent Test**: A logged-in user visits the invite link directly and is added to the workspace without being redirected to login or register.

**Acceptance Scenarios**:

1. **Given** a logged-in user visits the invite link directly, **When** their e-mail matches the invite, **Then** they are added to the workspace immediately.
2. **Given** a logged-in user visits the invite link, **When** their e-mail does not match the invite, **Then** a clear error message is shown.

---

### Edge Cases

- What happens when the invite link is expired and the user tries to register via the redirect? Registration succeeds but the acceptance page shows an "invite expired" message.
- What happens when the redirect parameter points to an external URL? The system rejects it and falls back to the default post-auth destination.
- What happens when the invitee registers with a different e-mail than invited? The invite is not auto-accepted; the acceptance page shows an e-mail mismatch error.
- What happens when the same e-mail is invited twice before accepting? The pending invite guard remains in place; duplicate invites are rejected before sending the e-mail.
- What happens when the workspace is deleted before the invitee accepts? The acceptance page shows a clear error without breaking the auth flow.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST detect whether an invited e-mail address belongs to an existing account at the time the invite is sent.
- **FR-002**: System MUST send an e-mail to unregistered invitees with a link to the registration page containing the invite acceptance path as a redirect parameter.
- **FR-003**: System MUST send an e-mail to registered invitees with a link to the login page containing the invite acceptance path as a redirect parameter.
- **FR-004**: System MUST redirect users to the path specified in the redirect parameter after successful registration, when the parameter is present and valid.
- **FR-005**: System MUST redirect users to the path specified in the redirect parameter after successful login, when the parameter is present and valid.
- **FR-006**: System MUST validate redirect parameters to only accept internal paths, silently ignoring external URLs.
- **FR-007**: System MUST preserve the redirect parameter across form validation errors so it is not lost if the user must correct their input.
- **FR-008**: System MUST remove the session-based `invitation_token` mechanism entirely, relying solely on the redirect URL approach.
- **FR-009**: System MUST handle expired or invalid invite tokens gracefully on the acceptance page without disrupting the completed authentication.
- **FR-010**: System MUST allow already-authenticated users to access the invite acceptance page directly without re-authenticating.

### Key Entities

- **Invitation**: Pending workspace invite identified by a unique token, associated with a target e-mail, role, and expiry. No schema changes required.
- **Redirect Parameter**: A URL-encoded internal path passed as a query parameter through registration and login forms. Must be validated as an internal path before use.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of invited e-mails (registered or not) receive an e-mail notification with a functional invite link within 60 seconds of the invite being submitted.
- **SC-002**: Unregistered invitees who click the link and complete registration join the workspace in a single uninterrupted flow with zero additional navigation steps.
- **SC-003**: Registered invitees who click the link and log in join the workspace in a single uninterrupted flow with zero additional navigation steps.
- **SC-004**: Zero cases where a valid invite token is silently lost due to e-mail mismatch, logout, or session expiry — the redirect URL approach eliminates all such edge cases.
- **SC-005**: All redirect parameters pointing to external domains are rejected, with no open redirect vulnerabilities introduced.

## Assumptions

- The invite token remains the source of truth for invite validity and expiry (7 days). No changes to the `invitations` table schema are required.
- The existing `WorkspaceInviteNotification` will be updated to generate the correct link (register or login) — no new notification class is needed.
- Login and registration pages already render query parameters passed in the URL; only post-submission redirect handling is new.
- The session-based `invitation_token` mechanism introduced in PR #3 will be fully removed by this feature, superseding that PR.
- Rate limiting on invite submission remains unchanged.
