# Data Model: Workspace Management

**Feature**: Workspace Management
**Date**: 2026-03-21
**Purpose**: Define database schema, entity relationships, validation rules, and state transitions

## Entities

### Workspace

**Purpose**: Represents an isolated container for collaborative work, containing projects, members, and settings.

**Fields**:
- `id`: Primary key (bigint, auto-increment)
- `user_id`: Foreign key to User (owner), indexed
- `name`: Workspace name (string, max 255, required)
- `slug`: URL-friendly identifier (string, max 255, unique)
- `description`: Optional description (string, nullable)
- `logo_path`: Path to logo image (string, nullable)
- `created_at`: Timestamp of creation (datetime)
- `updated_at`: Timestamp of last update (datetime)

**Relationships**:
- `owner()`: BelongsTo User
- `members()`: BelongsToMany User (via Member pivot)
- `invitations()`: HasMany Invitation
- `projects()`: HasMany Project (assumes Project model exists)

**Validation Rules** (from Form Request):
- `name`: required, string, max:255
- `slug`: nullable, string, unique:workspaces
- `description`: nullable, string

**Indexes**:
- `user_id` (foreign key, index)
- `slug` (unique index)

**State Transitions**: None (workspace is a simple entity with CRUD operations)

---

### Member (Pivot Table)

**Purpose**: Represents the relationship between a User and a Workspace, storing the user's role within that workspace.

**Fields**:
- `id`: Primary key (bigint, auto-increment)
- `user_id`: Foreign key to User, indexed
- `workspace_id`: Foreign key to Workspace, indexed
- `role`: User's role (enum: Owner, Admin, Member, Viewer)
- `created_at`: Timestamp of membership creation (datetime)

**Relationships**:
- `user()`: BelongsTo User
- `workspace()`: BelongsTo Workspace

**Validation Rules**:
- `user_id`: required, exists:users
- `workspace_id`: required, exists:workspaces
- `role`: required, in:owner,admin,member,viewer

**Constraints**:
- Unique constraint on (user_id, workspace_id) - a user can only belong to a workspace once
- Cascade delete when workspace is deleted
- Cascade delete when user is deleted

**Indexes**:
- `user_id` (foreign key, index)
- `workspace_id` (foreign key, index)
- `(user_id, workspace_id)` (unique composite index)

**State Transitions**: Role can be updated (Owner → Admin → Member → Viewer or reverse)

---

### Invitation

**Purpose**: Represents a pending invitation for a user to join a workspace.

**Fields**:
- `id`: Primary key (bigint, auto-increment)
- `user_id`: Foreign key to User (who sent the invitation), indexed, nullable (for tracking)
- `workspace_id`: Foreign key to Workspace, indexed
- `email`: Email address of invitee (string, required)
- `role`: Role assigned to invitee (enum: Owner, Admin, Member, Viewer)
- `token`: Unique token for accepting invitation (string, unique)
- `accepted_at`: Timestamp of acceptance (datetime, nullable)
- `created_at`: Timestamp of invitation creation (datetime)

**Relationships**:
- `user()`: BelongsTo User (sender)
- `workspace()`: BelongsTo Workspace

**Validation Rules** (from Form Request):
- `email`: required, email, exists:users (optional - might be a new user)
- `role`: required, in:owner,admin,member,viewer
- Prevent duplicate invitations for same email + workspace combination

**Indexes**:
- `user_id` (foreign key, index)
- `workspace_id` (foreign key, index)
- `token` (unique index)
- `(workspace_id, email)` (unique composite index - prevent duplicate invitations)

**State Transitions**:
1. `created` → `accepted` (when invitee clicks accept link)
2. Can be invalidated if workspace is deleted (cascade delete)

**Expiration**: Token expires after 7 days (configurable via environment)

---

### User (Extended)

**Purpose**: Extended user model to support workspace relationships and role queries.

**Existing Fields**: (inherited from base User model)
- `id`, `name`, `email`, `password`, `created_at`, `updated_at`

**Additional Relationships**:
- `workspaces()`: BelongsToMany Workspace (via Member pivot)
- `ownedWorkspaces()`: HasMany Workspace (where user_id = user.id)

**New Methods**:
```php
public function workspaces(): BelongsToMany
{
    return $this->belongsToMany(Workspace::class)
        ->withPivot('role')
        ->withTimestamps();
}

public function ownedWorkspaces(): HasMany
{
    return $this->hasMany(Workspace::class);
}

public function roleIn(Workspace $workspace): ?WorkspaceRole
{
    return $this->workspaces()
        ->where('workspaces.id', $workspace->id)
        ->first()
        ?->pivot->role
        : null;
}
```

**Validation**: No new validation rules (uses existing User validation)

---

### WorkspaceRole (Enum)

**Purpose**: Type-safe representation of workspace role levels.

**Values**:
- `Owner` (value: 'owner') - Full access: create, read, update, delete, transfer ownership
- `Admin` (value: 'admin') - Manage members, update workspace settings, cannot delete/transfer ownership
- `Member` (value: 'member') - Create and edit content, cannot manage members or settings
- `Viewer` (value: 'viewer') - Read-only access, cannot create or edit content

**Hierarchy**: Owner > Admin > Member > Viewer (for permission checks)

---

### Project (Extended)

**Purpose**: Extended project model to belong to a workspace.

**Existing Fields**: (assumed from base Project model)
- `id`, `name`, `description`, `user_id` (creator), `created_at`, `updated_at`

**New Fields**:
- `workspace_id`: Foreign key to Workspace (required, indexed)

**New Relationships**:
- `workspace()`: BelongsTo Workspace

**Validation**:
- `workspace_id`: required, exists:workspaces

**Indexes**:
- `workspace_id` (foreign key, index)
- `(workspace_id, user_id)` (composite index for user's projects in workspace)

**Behavior**: Automatically scoped to current workspace via `BelongsToWorkspace` trait

---

## Entity Relationship Diagram (ERD)

```
User ────────────< Members (pivot) >──────────── Workspace
   │                    │              │
   │                    │              │
   │                    ├─role         ├─owner
   │                    │              ├─members
   │                    │              ├─invitations
   │                    │              └─projects
   │                    │
   ├─workspaces ◄────────┘
   │
   └─ownedWorkspaces ────────┘


Workspace ────────────── Invitations
   │                     │
   │                     ├─email
   │                     ├─role
   │                     ├─token
   │                     ├─accepted_at
   │                     └─workspace_id (FK)

Workspace ────────────── Projects
   │                     │
   │                     ├─workspace_id (FK)
   │                     └─scoped to workspace
```

---

## Database Schema (Migrations)

### 1. Create Workspaces Table

```php
Schema::create('workspaces', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('description')->nullable();
    $table->string('logo_path')->nullable();
    $table->timestamps();

    $table->index('user_id');
});
```

### 2. Create Members Table

```php
Schema::create('members', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
    $table->enum('role', ['owner', 'admin', 'member', 'viewer'])->default('member');
    $table->timestamp('created_at')->nullable();

    $table->unique(['user_id', 'workspace_id']);
    $table->index('user_id');
    $table->index('workspace_id');
});
```

### 3. Create Invitations Table

```php
Schema::create('invitations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
    $table->string('email');
    $table->enum('role', ['owner', 'admin', 'member', 'viewer']);
    $table->string('token')->unique();
    $table->timestamp('accepted_at')->nullable();
    $table->timestamps();

    $table->unique(['workspace_id', 'email']);
    $table->index('user_id');
    $table->index('workspace_id');
    $table->index('token');
});
```

### 4. Add Workspace ID to Projects Table

```php
Schema::table('projects', function (Blueprint $table) {
    $table->foreignId('workspace_id')->nullable()->constrained()->onDelete('set null');
    $table->index('workspace_id');
});
```

---

## Global Scopes & Traits

### BelongsToWorkspace Trait

```php
trait BelongsToWorkspace
{
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function ($query) {
            $currentWorkspace = Workspace::current();

            if ($currentWorkspace) {
                return $query->where('workspace_id', $currentWorkspace->id);
            }

            return $query;
        });
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    protected static function creating(Model $model): void
    {
        if (!$model->workspace_id && Workspace::current()) {
            $model->workspace_id = Workspace::current()->id;
        }
    }
}
```

### Workspace Static Helper

```php
class Workspace extends Model
{
    private static ?Workspace $current = null;

    public static function current(): ?Workspace
    {
        if (!static::$current && session('current_workspace_id')) {
            static::$current = static::find(session('current_workspace_id'));
        }

        return static::$current;
    }

    public static function setCurrent(?int $workspaceId): void
    {
        static::$current = $workspaceId ? static::find($workspaceId) : null;
        if ($workspaceId) {
            session(['current_workspace_id' => $workspaceId]);
        }
    }

    public static function forgetCurrent(): void
    {
        static::$current = null;
        session()->forget('current_workspace_id');
    }
}
```

---

## Validation Rules Summary

| Entity | Field | Rules | Error Message (Portuguese) |
|--------|--------|--------|-------------------------------|
| Workspace | name | required, string, max:255 | O campo nome é obrigatório |
| Workspace | slug | nullable, string, unique:workspaces | Este slug já está em uso |
| Workspace | description | nullable, string | - |
| Member | user_id | required, exists:users | Usuário inválido |
| Member | workspace_id | required, exists:workspaces | Workspace inválido |
| Member | role | required, in:owner,admin,member,viewer | Papel inválido |
| Invitation | email | required, email, unique_invite | Email inválido ou já convidado |
| Invitation | role | required, in:owner,admin,member,viewer | Papel inválido |
| Invitation | token | required, unique | Token inválido |
| Invitation | accepted_at | nullable, date | Data de aceitação inválida |

---

## Data Integrity Rules

1. **Workspace Ownership**: A user can own multiple workspaces, but each workspace has exactly one owner
2. **Unique Membership**: A user can belong to a workspace only once (unique constraint on user_id + workspace_id)
3. **Role Hierarchy**: Owner > Admin > Member > Viewer (for permission checks)
4. **Invitation Uniqueness**: One invitation per email per workspace (unique constraint on workspace_id + email)
5. **Token Uniqueness**: Each invitation token is unique across all invitations
6. **Cascade Deletes**:
   - Deleting a workspace deletes all members, invitations, and sets workspace_id to null in projects
   - Deleting a user deletes all their memberships and sent invitations
7. **Foreign Key Constraints**: All foreign keys are constrained and indexed
8. **Nullability**: Only optional fields (slug, description, logo_path, accepted_at, user_id in invitations) can be null

---

## Performance Considerations

1. **Indexes**: All foreign keys and frequently queried columns (slug, token, user_id) are indexed
2. **Eager Loading**: Relationships (members, projects) should be eager loaded when accessed in lists
3. **Composite Indexes**:
   - Members: (user_id, workspace_id) unique index for quick membership checks
   - Invitations: (workspace_id, email) unique index to prevent duplicate invitations
4. **Session Caching**: Current workspace is stored in session to avoid database queries
5. **Global Scopes**: Automatic workspace filtering prevents accidental data leaks

---

## Migration Order

1. `create_workspaces_table` (required first)
2. `create_members_table` (depends on workspaces)
3. `create_invitations_table` (depends on workspaces)
4. `add_workspace_id_to_projects_table` (modifies existing projects table)

---

## Rollback Strategy

All migrations include down() methods:

```php
// create_workspaces_table
Schema::dropIfExists('workspaces');

// create_members_table
Schema::dropIfExists('members');

// create_invitations_table
Schema::dropIfExists('invitations');

// add_workspace_id_to_projects_table
Schema::table('projects', function (Blueprint $table) {
    $table->dropForeign(['workspace_id']);
    $table->dropColumn('workspace_id');
});
```
