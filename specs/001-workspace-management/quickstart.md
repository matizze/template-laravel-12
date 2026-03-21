# Quickstart: Workspace Management

**Feature**: Workspace Management
**Date**: 2026-03-21
**Purpose**: Get started with workspace management feature implementation

## Overview

Workspace Management enables users to create multiple isolated workspaces, invite team members with role-based permissions, switch between workspaces, and scope all data by the current workspace context. This feature follows Laravel 12 conventions and strict TDD discipline.

## Key Concepts

### 1. Workspaces

A **workspace** is an isolated container for collaborative work. Users can belong to multiple workspaces with different roles in each.

**Example**: A user "John" belongs to:
- "Company A" as **Owner** (full control)
- "Personal Projects" as **Member** (create and edit)
- "Client Project X" as **Viewer** (read-only)

### 2. Roles

Four hierarchical roles control permissions:

| Role | Can Create | Can Edit | Can Delete | Can Manage Members | Can Transfer Ownership |
|------|------------|-----------|-------------|-------------------|-------------------------|
| Owner | ✅ | ✅ | ✅ | ✅ | ✅ |
| Admin | ✅ | ✅ | ❌ | ✅ | ❌ |
| Member | ✅ | ✅ | ❌ | ❌ | ❌ |
| Viewer | ❌ | ❌ | ❌ | ❌ | ❌ |

### 3. Workspace Scoping

All data (projects, briefings, etc.) is automatically filtered by the **current workspace**. Users only see data from their currently selected workspace.

**Implementation**: Use the `BelongsToWorkspace` trait on models that belong to a workspace:

```php
class Project extends Model
{
    use BelongsToWorkspace; // Automatically filters by current workspace
}
```

### 4. Session-Based Workspace Switching

The current workspace is stored in the session (`current_workspace_id`). A middleware ensures it's set on every request and validates user membership.

**Switching workspaces**:
```php
// In WorkspaceController
public function switch(Workspace $workspace): RedirectResponse
{
    session(['current_workspace_id' => $workspace->id]);
    return redirect()->back()->with('success', 'Workspace alterado');
}
```

## Getting Started

### Prerequisites

- Laravel 12 project
- PHP 8.4.1+
- SQLite (dev) or PostgreSQL (prod)
- PHPUnit for testing
- Laravel Dusk for browser tests

### Installation

All code follows Laravel conventions. Use artisan commands to generate scaffolding:

```bash
# Generate models with migrations
php artisan make:model Workspace
php artisan make:model Member
php artisan make:model Invitation

# Generate controllers
php artisan make:controller WorkspaceController
php artisan make:controller MemberController

# Generate form requests
php artisan make:request CreateWorkspaceRequest
php artisan make:request InviteMemberRequest

# Generate policies
php artisan make:policy WorkspacePolicy
php artisan make:policy ProjectPolicy

# Generate migrations
php artisan make:migration create_workspaces_table
php artisan make:migration create_members_table
php artisan make:migration create_invitations_table
```

### Running Migrations

```bash
php artisan migrate
```

### Running Tests

```bash
# Run feature tests
php artisan test --compact

# Run specific test
php artisan test --filter=test_user_can_create_workspace

# Run browser tests (slower)
php artisan dusk
```

## Common Patterns

### Creating a Workspace

```php
// Feature test
public function test_user_can_create_workspace(): void
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post('/workspace', [
            'name' => 'My Workspace',
            'description' => 'Workspace description',
        ]);

    $response->assertRedirect('/dashboard');
    $this->assertDatabaseHas('workspaces', [
        'name' => 'My Workspace',
        'slug' => 'my-workspace',
        'user_id' => $user->id,
    ]);
}
```

### Inviting a Member

```php
// Feature test
public function test_owner_can_invite_member(): void
{
    $workspace = Workspace::factory()->create();
    $user = User::factory()->create(['id' => 1]);

    $response = $this->actingAs($workspace->owner)
        ->post("/workspace/{$workspace->id}/invite", [
            'email' => 'newuser@example.com',
            'role' => 'member',
        ]);

    $this->assertDatabaseHas('invitations', [
        'workspace_id' => $workspace->id,
        'email' => 'newuser@example.com',
        'role' => 'member',
    ]);
}
```

### Switching Workspaces

```php
// Feature test
public function test_user_can_switch_workspace(): void
{
    $user = User::factory()->create();
    $workspace1 = Workspace::factory()->create();
    $workspace2 = Workspace::factory()->create();

    $user->workspaces()->attach([$workspace1->id, $workspace2->id]);

    $response = $this->actingAs($user)
        ->post("/workspace/switch/{$workspace2->id}");

    $response->assertSessionHas('current_workspace_id', $workspace2->id);
}
```

### Checking User Role in Workspace

```php
// Policy
public function update(User $user, Workspace $workspace): bool
{
    return $user->roleIn($workspace) === WorkspaceRole::Owner;
}

// Usage in controller
public function update(Workspace $workspace, UpdateSettingsRequest $request): RedirectResponse
{
    $this->authorize('update', $workspace);

    $workspace->update($request->validated());

    return redirect()->back()->with('success', 'Workspace atualizado');
}
```

### Using Workspace Scoping

```php
// Model with trait
class Project extends Model
{
    use BelongsToWorkspace;
}

// Queries automatically filtered by current workspace
$projects = Project::all(); // Only projects in current workspace

// To disable scoping (for admin operations)
$allProjects = Project::withoutGlobalScope('workspace')->get();
```

## Testing Guidelines

### Feature Tests

**Use feature tests for**: HTTP request/response validation, database operations, authorization checks

```php
public function test_workspace_creation_validation(): void
{
    $user = User::factory()->create();

    // Missing name
    $response = $this->actingAs($user)
        ->post('/workspace', ['description' => 'Test']);

    $response->assertSessionHasErrors('name');
}
```

### Browser Tests

**Use Dusk for**: JavaScript interactions (modals, Alpine.js), flash notifications, UI flows

```php
public function test_user_can_open_create_workspace_modal(): void
{
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/dashboard')
            ->click('@open-create-workspace-modal')
            ->waitForText('Criar Workspace', 5)
            ->assertVisible('@create-workspace-modal');
    });
}
```

### Test Naming Convention

```php
// Format: test_<subject>_<action>_<expected_outcome>
test_user_can_create_workspace()
test_user_cannot_create_workspace_without_name()
test_workspace_slug_is_generated_from_name()
test_admin_can_invite_member()
test_viewer_cannot_delete_workspace()
test_owner_can_transfer_ownership()
```

## File Organization

```
app/
├── Models/
│   ├── Workspace.php
│   ├── Member.php
│   └── Invitation.php
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Policies/
└── Traits/
    └── BelongsToWorkspace.php

resources/views/
├── components/
│   ├── workspace-switcher.blade.php
│   └── create-workspace-modal.blade.php
└── dashboard/
    └── workspaces/
        └── create.blade.php

tests/
├── Feature/
│   ├── WorkspaceTest.php
│   └── MemberTest.php
└── Browser/
    └── WorkspaceFlowTest.php
```

## Common Issues & Solutions

### Issue: Workspace scoping not working

**Cause**: Model doesn't use `BelongsToWorkspace` trait

**Solution**: Add the trait to the model:
```php
class Project extends Model
{
    use BelongsToWorkspace;
}
```

### Issue: Invitation token not unique

**Cause**: Two invitations with same token

**Solution**: Ensure `token` column has unique index in migration:
```php
$table->string('token')->unique();
```

### Issue: User can access workspace they don't belong to

**Cause**: Missing policy check on route

**Solution**: Add policy middleware:
```php
Route::put('/workspace/{workspace}', [WorkspaceController::class, 'update'])
    ->middleware('can:update,workspace');
```

### Issue: Workspace switching not persisting

**Cause**: Session not being saved or middleware not registered

**Solution**: Ensure middleware is registered in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->web();
    $middleware->alias(App\Http\Middleware\SetCurrentWorkspace::class, 'set.current.workspace');
})
```

## Performance Tips

1. **Eager load relationships**:
   ```php
   $workspaces = Workspace::with('members')->get();
   ```

2. **Use database indexes**:
   - Foreign keys automatically indexed
   - Frequently queried columns (slug, token) should have indexes

3. **Session caching**:
   - Current workspace is cached in session
   - Avoid querying workspace on every page

4. **Select only needed columns**:
   ```php
   $projects = Project::select(['id', 'name'])->get();
   ```

## Security Considerations

1. **Authorization**: Always use policies, never hardcode role checks in controllers
2. **CSRF**: All forms must include CSRF token (`@csrf`)
3. **Validation**: Use Form Requests for all user input
4. **Rate limiting**: Protect sensitive routes (invite, delete) with `throttle` middleware
5. **Email verification**: Invitation tokens must be unique and expire after 7 days
6. **Foreign key constraints**: All relationships have database-level constraints

## Next Steps

1. Review [data-model.md](./data-model.md) for complete schema
2. Read [research.md](./research.md) for technical decisions
3. Check constitution: `.specify/memory/constitution.md`
4. Start TDD cycle: Write failing test → Implement → Refactor
5. Run `vendor/bin/pint` for code formatting
6. Run `./vendor/bin/phpstan analyse` for static analysis

## Additional Resources

- Laravel Documentation: https://laravel.com/docs/12.x
- Laravel Policies: https://laravel.com/docs/12.x/authorization
- Laravel Testing: https://laravel.com/docs/12.x/testing
- Project Constitution: `.specify/memory/constitution.md`
