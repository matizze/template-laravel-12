# Quickstart: Invite Redirect Flow

## How the New Flow Works

### Inviting an Unregistered User

1. Admin submits invite form for `new@example.com`
2. `MemberController::invite()` creates the `Invitation` record
3. Detects `new@example.com` has no account → sends email via on-demand notification
4. Email contains link: `https://app.test/register?redirect=%2Finvitation%2F{token}`
5. User clicks link → lands on `/register?redirect=/invitation/{token}`
6. Register form has `<input type="hidden" name="redirect" value="/invitation/{token}">`
7. User completes registration → `RegisterController::store()` validates redirect → redirects to `/invitation/{token}`
8. `MemberController::accept()` runs — user is now authenticated → invite accepted

### Inviting a Registered User

1. Admin submits invite form for `existing@example.com`
2. `MemberController::invite()` detects user has an account → sends email to their User model
3. Email contains link: `https://app.test/login?redirect=%2Finvitation%2F{token}`
4. User clicks link → lands on `/login?redirect=/invitation/{token}`
5. Login form has `<input type="hidden" name="redirect" value="/invitation/{token}">`
6. User logs in → `LoginController::store()` validates redirect → redirects to `/invitation/{token}`
7. `MemberController::accept()` runs — user authenticated → invite accepted

### Direct Access While Logged In

1. Authenticated user visits `/invitation/{token}` directly
2. `MemberController::accept()` runs → checks email match → accepts invite immediately

### Unauthenticated Direct Access (no email link)

1. User visits `/invitation/{token}` without being logged in
2. `accept()` detects no user → checks if invitee email has account:
   - Has account → redirect to `/login?redirect=/invitation/{token}`
   - No account → redirect to `/register?redirect=/invitation/{token}`
3. After auth → redirect completes the invite acceptance

## Security: Open Redirect Prevention

The redirect param is validated before use:

```php
private function isValidRedirect(string $redirect): bool
{
    return str_starts_with($redirect, '/') && ! str_starts_with($redirect, '//');
}
```

Invalid redirects silently fall back to the default post-auth destination.
