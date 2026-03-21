# Data Model: Invite Redirect Flow

## No Schema Changes Required

The `invitations` table schema is unchanged. The invite token remains the source of truth for invite validity and expiry.

## Key Entities

### Invitation (existing)

| Field         | Type      | Notes                          |
|---------------|-----------|--------------------------------|
| id            | ulid      | Primary key                    |
| workspace_id  | ulid      | Foreign key → workspaces       |
| email         | string    | Invited email address          |
| role          | enum      | WorkspaceRole enum             |
| token         | string    | Unique invite token (uuid)     |
| user_id       | ulid      | Inviter (foreign key → users)  |
| accepted_at   | timestamp | Null until accepted            |
| expires_at    | timestamp | 7 days from creation           |
| created_at    | timestamp |                                |
| updated_at    | timestamp |                                |

### Redirect Parameter (new concept, no table)

| Property  | Description                                          |
|-----------|------------------------------------------------------|
| name      | `redirect` (query parameter name)                    |
| format    | URL-encoded internal path, e.g. `/invitation/{token}`|
| validation| Must start with `/` and not `//` (no external URLs)  |
| lifetime  | Travels via form hidden input; not stored in session  |
