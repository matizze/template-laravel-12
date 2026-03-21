# Research: Workspace Management

**Feature**: Workspace Management
**Date**: 2026-03-21
**Purpose**: Document technical decisions and research findings for implementation

## Technical Decisions

### 1. Workspace Storage & Data Model

**Decision**: Use Eloquent models with pivot table for User-Workspace relationships

**Rationale**: Laravel's `belongsToMany` with pivot data is the idiomatic way to handle many-to-many relationships with additional attributes (role). Using a separate `Member` model representing the pivot allows for explicit relationships and queries.

**Alternatives considered**:
- Using a direct User-Workspace table without pivot model: Less explicit, harder to add business logic
- JSON column for user's workspaces in User table: Not queryable, violates normalization principles

**Implementation**: 
- `User::workspaces()` returns `BelongsToMany` with pivot `role`
- `Workspace::members()` returns `BelongsToMany` with pivot `role`
- `Member` model extends `Pivot` for explicit type hints

---

### 2. Role-Based Authorization

**Decision**: Use Laravel Policies for workspace authorization, Enums for role constants

**Rationale**: Laravel Policies are the framework-idiomatic way to handle authorization. They integrate seamlessly with controllers and can be tested independently. Using PHP 8.1+ Enums provides type safety for role comparisons.

**Alternatives considered**:
- Gates: Too granular for complex permission matrices
- Middleware roles check: Violates single-responsibility, hard to test
- Role checks in controllers: Not reusable, violates constitution principle VII

**Implementation**:
```php
enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';
}

// Policy method
public function update(User $user, Workspace $workspace): bool
{
    return $user->roleIn($workspace) === WorkspaceRole::Owner;
}
```

---

### 3. Workspace Scoping

**Decision**: Use global Eloquent scope via `BelongsToWorkspace` trait

**Rationale**: Global scopes automatically filter queries across all models that use the trait. This prevents data leaks where a user might accidentally see data from another workspace. Session-based current workspace is stored and accessed via a static helper.

**Alternatives considered**:
- Manual filtering in every query: Error-prone, violates DRY
- Query builder macros: Less discoverable than traits
- Middleware-based scoping: Would require route parameter injection, less flexible

**Implementation**:
```php
// Trait
trait BelongsToWorkspace
{
    protected static function booted(): void
    {
        static::addGlobalScope('workspace', function ($query) {
            return $query->where('workspace_id', Workspace::current()->id);
        });
    }
}

// Model
class Project extends Model
{
    use BelongsToWorkspace;
}

// Helper
class Workspace extends Model
{
    private static ?Workspace $current = null;

    public static function current(): ?Workspace
    {
        return static::$current ?? session('current_workspace_id');
    }
}
```

---

### 4. Invitation System

**Decision**: Secure token-based invitations via email with Laravel notifications

**Rationale**: Laravel's notification system abstracts email sending and supports multiple channels (mail, database, broadcast). Tokens ensure only the intended recipient can accept. Using the built-in `Password` broker's token generation pattern is a proven secure approach.

**Alternatives considered**:
- Public invitation links without tokens: Security risk, anyone could join
- Direct member addition without acceptance: Bad UX for team onboarding
- Custom token generation: Re-inventing wheel, security risk

**Implementation**:
```php
class Invitation extends Model
{
    public function accept(User $user): void
    {
        $this->workspace->members()->attach($user, ['role' => $this->role]);
        $this->update(['accepted_at' => now()]);
    }
}

class WorkspaceInviteNotification extends Notification
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Convite para participar de um workspace')
            ->actionUrl(route('invitation.accept', $this->token));
    }
}
```

---

### 5. Workspace Switching

**Decision**: Session-based current workspace ID with automatic fallback

**Rationale**: Storing the current workspace in the session persists across requests without requiring database queries on every page load. A middleware ensures the workspace is set and validates user membership. Fallback to user's first workspace provides good UX.

**Alternatives considered**:
- URL parameter on every request: Not user-friendly, breaks bookmarking
- Cookie-based: Less secure than session
- Database-backed per-user setting: Requires query on every request

**Implementation**:
```php
class SetCurrentWorkspace implements Middleware
{
    public function handle($request, Closure $next): Response
    {
        $workspaceId = session('current_workspace_id');

        if (!$workspaceId) {
            $workspace = $request->user()->workspaces()->first();
            session(['current_workspace_id' => $workspace->id]);
        }

        Workspace::setCurrent($workspaceId);

        return $next($request);
    }
}
```

---

### 6. Form Validation

**Decision**: Form Request classes with array-based validation rules and custom messages

**Rationale**: Form Requests separate validation logic from controllers, making controllers cleaner and tests easier. Array-based rules with custom error messages match the constitution's requirements.

**Alternatives considered**:
- Inline validation in controllers: Violates constitution principle VII, hard to test
- Frontend validation only: Not secure, can be bypassed
- Third-party validation libraries: Unnecessary, Laravel's validation is sufficient

**Implementation**:
```php
class CreateWorkspaceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'unique:workspaces'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório',
            'slug.unique' => 'Este slug já está em uso',
        ];
    }
}
```

---

### 7. Testing Strategy

**Decision**: Feature tests (PHPUnit) for 90% of coverage, Dusk tests for critical user journeys

**Rationale**: Feature tests are fast, reliable, and test the full HTTP stack. They cover happy paths, error paths, and authorization scenarios. Dusk tests are reserved for interactions involving JavaScript (modals, Alpine.js, flash notifications) where the DOM is important.

**Alternatives considered**:
- Only feature tests: Cannot verify JavaScript behavior properly
- Only Dusk tests: Too slow, flaky, resource-intensive
- Unit tests for everything: Don't test integration, controllers, policies

**Implementation**:
```php
// Feature test - covers auth + validation + database
public function test_user_can_create_workspace(): void
{
    $user = User::factory()->create();
    $response = $this->actingAs($user)
        ->post('/workspace', ['name' => 'My Workspace']);

    $response->assertRedirect('/dashboard');
    $this->assertDatabaseHas('workspaces', ['name' => 'My Workspace']);
}

// Dusk test - covers JavaScript + DOM + UI flow
public function test_user_can_switch_workspace_via_ui(): void
{
    $user = User::factory()->create();
    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/dashboard')
            ->click('@workspace-switcher')
            ->waitForText('Workspace B', 5)
            ->click('@switch-to-workspace-b')
            ->waitForLocation('/dashboard', 5);
    });
}
```

---

### 8. Email Configuration

**Decision**: Use Laravel's mail queue with environment-based configuration

**Rationale**: Queuing emails prevents request delays and improves reliability. Laravel's mail system supports multiple drivers (SMTP, Mailgun, SES, etc.) making it easy to switch providers based on environment.

**Alternatives considered**:
- Synchronous email sending: Blocks request, bad UX
- Third-party email service SDK: Tightly coupled, less flexible
- Database-only notifications: Doesn't meet spec requirement for email

**Implementation**:
```php
// config/mail.php
'connections' => [
    'smtp' => [
        'transport' => 'smtp',
        'host' => env('MAIL_HOST'),
        'queue' => env('MAIL_QUEUE', 'default'),
    ],
],

// Queue configuration
'connections' => [
    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => env('QUEUE_CONNECTION', 'database'),
    ],
],
```

---

### 9. Performance Considerations

**Decision**: Eager loading relationships, database indexes, session caching for current workspace

**Rationale**: Eager loading prevents N+1 query problems. Indexes on foreign keys and frequently queried columns speed up joins and lookups. Session caching avoids database queries for the current workspace on every request.

**Alternatives considered**:
- No eager loading: Would cause N+1 query problems
- Query result caching: More complex, harder to invalidate
- No indexes: Poor performance at scale

**Implementation**:
```php
// Eager loading
$workspaces = Workspace::with('members')->get();

// Migration with indexes
Schema::create('workspaces', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('name');
    $table->string('slug')->unique();
    $table->timestamps();

    $table->index('user_id');
});

// Session middleware
class SetCurrentWorkspace implements Middleware
{
    public function handle($request, Closure $next): Response
    {
        // Cache workspace in static property within request
        Workspace::setSession($request->user()->workspaces()->first());
        return $next($request);
    }
}
```

---

### 10. Security Measures

**Decision**: CSRF protection on all forms, rate limiting on sensitive routes, policy-based authorization

**Rationale**: CSRF tokens prevent cross-site request forgery attacks. Rate limiting prevents brute force attacks on invitation endpoints. Policy-based authorization ensures users cannot access data they shouldn't.

**Alternatives considered**:
- No CSRF protection: Major security vulnerability
- IP-based blocking: Too aggressive, can block legitimate users
- Hardcoded role checks in controllers: Violates constitution, hard to test

**Implementation**:
```php
// Routes
Route::middleware(['throttle:10,1'])->group(function () {
    Route::post('/workspace/invite', [MemberController::class, 'invite']);
});

// Middleware
Route::middleware(['web', 'auth'])->group(function () {
    Route::resource('workspaces', WorkspaceController::class);
});

// Policy (enforced via middleware)
Route::post('/workspace/{workspace}', [WorkspaceController::class, 'update'])
    ->middleware('can:update,workspace');
```

---

## Technology Stack Summary

| Component | Technology | Version | Rationale |
|-----------|-------------|----------|------------|
| Backend Framework | Laravel | 12 | Project constitution, current version |
| Language | PHP | 8.4.1+ | Constitution requirement, modern features |
| Database (dev) | SQLite | Latest | Fast for testing, built-in |
| Database (prod) | PostgreSQL | Latest | Constitution requirement |
| ORM | Eloquent | Laravel 12 | Framework-idiomatic, tested |
| Frontend | Blade + Tailwind v4 | Latest | Constitution requirement |
| Interactivity | Alpine.js | v3 | Lightweight, directive-based |
| Testing (feature) | PHPUnit | 11 | Laravel 12 default |
| Testing (E2E) | Laravel Dusk | Latest | JavaScript testing |
| Notifications | PHPFlasher + Noty | Latest | Project uses this stack |
| Icons | Blade-Lucide-Icons | Latest | Constitution requirement |
| Asset Bundling | Vite | 7 | Laravel 12 default |

---

## Open Decisions (Deferred to Planning/Implementation)

1. **Invitation token expiration duration**: 7 days is industry standard, but can be configured via environment variable
2. **Maximum workspaces per user**: No explicit limit in spec, suggest 100 as reasonable default
3. **Maximum members per workspace**: No explicit limit in spec, suggest 50 as reasonable default
4. **Slug generation algorithm**: Use `Str::slug()` with random suffix to ensure uniqueness
5. **Logo storage**: Use local filesystem storage for now, can migrate to S3 later if needed

---

## References

- Laravel 12 Documentation: https://laravel.com/docs/12.x
- Laravel Policies: https://laravel.com/docs/12.x/authorization
- Laravel Notifications: https://laravel.com/docs/12.x/notifications
- Laravel Dusk: https://laravel.com/docs/12.x/dusk
- PHPFlasher: https://php-flasher.io/docs
- Alpine.js: https://alpinejs.dev/
