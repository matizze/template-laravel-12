<!-- SYNC IMPACT REPORT
Version: 1.2.0 → 1.3.0 (MINOR - Code Clarity & Comments enforcement)
Status: COMPLETE testing + code quality guidance
Modified Principles: Principle IV expanded (Type Safety + Code Clarity & Comments)
Added Sections: Code Clarity & Comments Examples section with:
  - 8+ BAD comment examples (idiotic/obvious)
  - 6+ GOOD comment examples (intent-based)
  - Decision tree: when comments are actually needed
  - Clear rules: comments explain WHY, never WHAT
  - PHPDoc best practices with examples
Removed Sections: N/A
Templates Updated: None required
Deferred TODOs: None
-->

# Template Laravel 12 Constitution

## Core Principles

### I. Laravel Way First

All development MUST follow Laravel 12 conventions and patterns. Never reinvent or bypass framework features.

- Use `php artisan make:` commands for all code generation (migrations, models, controllers, requests, policies, etc.)
- Prefer Eloquent ORM over raw queries; avoid `DB::` when relationships exist
- Use built-in Laravel auth, gates, and policies for access control
- Resource controllers follow REST conventions; route naming uses dot notation
- Form Requests handle all validation with array-based rules and custom messages
- Flash messages use only keys: `success`, `error`, `warning`, `info` (never `status` or `message`)

**Rationale**: Framework conventions reduce cognitive load, ensure consistency, and leverage battle-tested patterns maintained by Laravel core team.

---

### II. Convention Over Configuration

Project structure, naming, and file organization MUST follow Laravel conventions without custom configuration.

- Views organized by route: `resources/views/{route}/` (e.g., `auth/`, `dashboard/`, `settings/`)
- Blade components use kebab-case names (e.g., `user-menu`, `nav-item`, not `UserMenu`)
- Models and database tables follow singular/plural conventions
- Controller methods match REST actions: `index`, `show`, `create`, `store`, `edit`, `update`, `destroy`
- Environment variables only in `config/` files; never use `env()` outside of config
- `.env.example` kept in sync with all required environment variables

**Rationale**: Reduces decision fatigue, enables new team members to navigate codebase instantly, minimizes configuration drift.

---

### III. Test-Driven Development / TDD (NON-NEGOTIABLE)

All features MUST follow strict Test-Driven Development (TDD): RED → GREEN → REFACTOR cycle.

- **RED Phase**: Write failing test FIRST, before any implementation code exists
  - Test must compile but fail execution (assertion fails)
  - Test name clearly describes expected behavior (e.g., `test_user_can_create_workspace`)
  - Test file created with `php artisan make:test` (feature tests by default)
- **GREEN Phase**: Write minimal implementation to make test pass
  - Implement only what's needed to pass the test
  - Do NOT over-engineer or add features beyond test requirements
  - All tests MUST pass: `php artisan test --compact`
- **REFACTOR Phase**: Improve code quality without changing behavior
  - Extract duplication, improve variable names, optimize queries
  - Tests remain green throughout refactoring
  - Run tests after each refactoring step

**Test Coverage Requirements**:
- All controllers, policies, models, form requests MUST have tests
- Feature tests use real SQLite database (in-memory), never mock the database
- Each feature test covers: happy path, validation/error paths, authorization scenarios
- Unit tests (with `--unit`) allowed for complex business logic; prefer feature tests for integration
- Tests MUST NOT use `assertSessionHas()` for flash keys (`success`, `error`, etc.) — PHPFlasher consumes them

**TDD Discipline**:
- No implementation code commits without corresponding test commits
- Test files committed before feature branch merges
- Pull request MUST NOT be created until tests pass locally
- Code reviews verify test coverage (did all paths get tested?)
- Failing tests BLOCK merge; author must fix or abandon PR

**Rationale**: TDD ensures specifications are executable, prevents over-engineering, enables fearless refactoring, and catches edge cases before production.

---

### IV. Type Safety & Modern PHP

All code MUST use PHP 8.2+ features: constructor property promotion, typed properties, union types, match expressions.

- All methods and functions MUST have explicit return type declarations
- All method parameters MUST have type hints (use `?Type` for nullable)
- Constructor property promotion used for dependency injection (no empty constructors)
- Enums use TitleCase keys (e.g., `Owner`, `Admin`, `Member`)
- Never suppress errors with `@` operator; handle or log instead

**Code Clarity & Comments** (NON-NEGOTIABLE):
- Code MUST be self-explanatory. If method/variable names are unclear, rename them instead of adding comments.
- Comments ONLY for "why", never for "what" — code already shows the "what"
- NO IDIOTIC/OBVIOUS COMMENTS allowed: `$count++; // increment count` ❌
- PHPDoc blocks ONLY for: method signatures, complex return types, business logic intent
- Inline comments ONLY for: non-obvious algorithms, workarounds, performance tradeoffs
- If you need a comment to explain code, the code is probably unclear. Refactor instead.

**Rationale**: Type safety catches bugs at code-review time, enables IDE autocompletion, and self-explanatory code reduces cognitive load.

---

### V. Production-Ready by Default

All features MUST be deployment-ready: error handling, security, observability, and scaling in place.

- Middleware stack configured in `bootstrap/app.php` (Laravel 12 structure)
- Rate limiting on auth routes: `throttle:5,1` (login/register), `throttle:3,1` (password reset)
- Authorization enforced via Policies and Gates, never hardcoded role checks
- Database migrations include indexes, constraints, and foreign keys
- Artisan commands run with `--no-interaction` flag in CI/CD
- Docker + Octane support: no changes break deployment pipeline
- SQL migrations are reversible and include rollback logic

**Rationale**: Prevents security vulnerabilities, supports high-traffic scaling, and enables confident production deploys.

---

### VI. Component-Based UI (Blade + Tailwind)

All UI MUST be built with reusable Blade components styled with Tailwind CSS v4.

- Components stored in `resources/views/components/` with kebab-case names
- Tailwind v4 imported via `@import "tailwindcss"` (not v3 `@tailwind` directives)
- Custom theme colors defined in `resources/css/app.css` using `@theme` directive
- Dark mode support: use `dark:` utilities if any page uses dark mode
- Icons via `blade-lucide-icons` package: `<x-icon:name="lucide-{icon}" />`
- No inline styles; all styling via Tailwind classes
- Responsive design: mobile-first, use breakpoint utilities (`md:`, `lg:`, etc.)

**Rationale**: Reusable components reduce duplication, Tailwind ensures consistency, v4 simplifies maintenance.

---

### VII. Code Quality Gates (NON-NEGOTIABLE)

All code MUST pass automated checks before merge. Quality gates are automated, not optional.

- **Formatting**: `vendor/bin/pint --dirty` MUST pass (Laravel Pint, PSR-12)
- **Static Analysis**: `./vendor/bin/phpstan analyse` MUST pass (Level 3)
- **Tests**: `php artisan test --compact` MUST pass (100% of suite)
- **Security**: No hardcoded secrets, credentials, or sensitive data in code
- **Database**: All migrations reversible, no manual SQL, indexes on foreign keys
- **Git Commits**: Clear messages, follow project conventions (see CLAUDE.md)

**Rationale**: Automated gates prevent bad code from merging, reduce code-review friction, and catch bugs early.

---

## Code Clarity & Comments Examples

### ❌ BAD Comments (Idiotic/Obvious)

```php
// ❌ BAD: Comment says exactly what the code already says
class User extends Model
{
    // Get workspaces
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class);
    }

    // Increment login count
    public function incrementLoginCount(): void
    {
        $this->login_count++; // Increment by 1
        $this->save(); // Save to database
    }

    // Check if user is admin
    public function isAdmin(): bool
    {
        return $this->role === 'admin'; // Return true if admin
    }

    // Delete user
    public function delete(): bool
    {
        return parent::delete();
    }
}

// ❌ BAD: Loop comment that explains what loops do
foreach ($users as $user) {
    // Loop through users
    $user->notify();
}

// ❌ BAD: Variable assignment comment
$timestamp = now(); // Get current timestamp
$workspace_id = $request->input('workspace_id'); // Get workspace ID from request
```

**Why it's bad**: Method names, types, and code are already clear. Comments add noise.

---

### ✅ GOOD Comments (With Intent)

```php
// ✅ GOOD: No obvious comments, clear naming
class User extends Model
{
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class);
    }

    public function incrementLoginCount(): void
    {
        $this->login_count++;
        $this->save();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}

// ✅ GOOD: Comment explains WHY, not WHAT
foreach ($users as $user) {
    // Notify users after batch operation completes to avoid email spam
    $user->notify();
}

// ✅ GOOD: Complex algorithm with explanation
public function generateWorkspaceSlug(string $name): string
{
    // Use UUID suffix to avoid collisions when multiple workspaces
    // have the same name (e.g., "Company A" from different users)
    $slug = Str::slug($name);
    $suffix = Str::random(6);
    
    return "{$slug}-{$suffix}";
}

// ✅ GOOD: Workaround with explanation
public function formatPhoneNumber(string $phone): string
{
    // Strip all non-digits first
    // API expects strict format, but users input various formats
    $clean = preg_replace('/\D/', '', $phone);
    
    return '(' . substr($clean, 0, 3) . ') ' . 
           substr($clean, 3, 3) . '-' . 
           substr($clean, 6, 4);
}

// ✅ GOOD: PHPDoc for complex return types
/**
 * Get all workspaces with member counts, filtered by role.
 * 
 * @param WorkspaceRole $minRole Minimum role required (Owner > Admin > Member)
 * @return Collection<Workspace> Workspaces where user has at least minRole
 */
public function getAccessibleWorkspaces(WorkspaceRole $minRole): Collection
{
    return $this->workspaces()
        ->wherePivot('role', '>=', $minRole->value)
        ->get();
}
```

---

### Decision Tree: Do I Need a Comment?

```
Does the code explain itself with clear names & types?
├─ YES → NO COMMENT NEEDED ✅
└─ NO
   ├─ Can I rename variables/methods to be clearer?
   │  ├─ YES → REFACTOR, then no comment ✅
   │  └─ NO (genuinely complex)
   │     ├─ Is it "why" (business logic, intent, tradeoffs)?
   │     │  ├─ YES → ADD COMMENT explaining the "why" ✅
   │     │  └─ NO (explains "what")
   │     │     └─ REFACTOR into smaller functions ✅
```

---

## Test-Driven Development (TDD) Workflow

### The TDD Cycle (RED → GREEN → REFACTOR)

Every feature follows this strict discipline:

#### 1️⃣ RED Phase: Write Failing Test

Start with an **empty implementation**. Write test FIRST that will fail.

```php
// tests/Feature/WorkspaceTest.php
public function test_user_can_create_workspace(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->post('/workspace', [
        'name' => 'My Company',
        'slug' => 'my-company',
    ]);
    
    $response->assertRedirect('/workspace/my-company');
    $this->assertDatabaseHas('workspaces', [
        'name' => 'My Company',
        'slug' => 'my-company',
    ]);
}
```

**Test Status**: 🔴 **RED** — Test fails (WorkspaceController doesn't exist yet)

Command: `php artisan test --filter=test_user_can_create_workspace`
```
FAILED tests/Feature/WorkspaceTest.php
Target class [App\Http\Controllers\WorkspaceController] does not exist.
```

#### 2️⃣ GREEN Phase: Write Minimal Implementation

Write **only the code needed** to make the test pass. No features beyond what test requires.

```php
// app/Http/Controllers/WorkspaceController.php
public function store(CreateWorkspaceRequest $request): RedirectResponse
{
    $workspace = Workspace::create($request->validated());
    return redirect()->route('workspace.show', $workspace);
}
```

```php
// app/Http/Requests/CreateWorkspaceRequest.php
public function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'slug' => 'required|string|unique:workspaces',
    ];
}
```

**Test Status**: 🟢 **GREEN** — Test passes

Command: `php artisan test --filter=test_user_can_create_workspace`
```
PASSED tests/Feature/WorkspaceTest.php::test_user_can_create_workspace
```

#### 3️⃣ REFACTOR Phase: Improve Code Quality

Tests are passing. Now improve code without breaking tests.

```php
// app/Models/Workspace.php — Add casts and relationships
protected function casts(): array
{
    return ['created_at' => 'datetime', 'updated_at' => 'datetime'];
}

public function members(): BelongsToMany
{
    return $this->belongsToMany(User::class)
        ->withPivot('role')
        ->withTimestamps();
}
```

**Test Status**: 🟢 **GREEN** — Tests still pass

Command: `php artisan test --compact`
```
Tests: 47 passed
```

### TDD Best Practices

**Write Tests That Fail For the Right Reasons**
```php
// ✅ GOOD: Test fails because feature doesn't exist
public function test_user_can_create_workspace(): void
{
    $response = $this->post('/workspace', ['name' => 'Test']);
    // Fails: Route doesn't exist yet
}

// ❌ BAD: Test fails due to bad test code
public function test_user_can_create_workspace(): void
{
    $response = $this->post('/workspaces', ['name' => 'Test']); // typo
    // Fails, but for wrong reason (typo in route)
}
```

**Test Behavior, Not Implementation**
```php
// ✅ GOOD: Tests behavior/outcome
public function test_workspace_slug_generated_from_name(): void
{
    $workspace = Workspace::create(['name' => 'My Workspace']);
    $this->assertEquals('my-workspace', $workspace->slug);
}

// ❌ BAD: Tests implementation details
public function test_workspace_slug_uses_str_slug(): void
{
    $this->assertTrue(Str::slug('My Workspace') === 'my-workspace');
    // This doesn't test Workspace class at all!
}
```

**One Assertion Per Test (Or Logically Related)**
```php
// ✅ GOOD: Single logical assertion
public function test_user_cannot_create_workspace_with_duplicate_slug(): void
{
    Workspace::factory()->create(['slug' => 'my-company']);
    
    $response = $this->post('/workspace', [
        'name' => 'Another Company',
        'slug' => 'my-company',
    ]);
    
    $response->assertSessionHasErrors('slug');
}

// ✅ ALSO GOOD: Multiple related assertions for integration
public function test_user_can_create_and_view_workspace(): void
{
    $response = $this->post('/workspace', ['name' => 'Test']);
    $workspace = Workspace::first();
    
    $this->assertDatabaseHas('workspaces', ['name' => 'Test']);
    $this->assertEquals('test', $workspace->slug);
    $response->assertRedirect(route('workspace.show', $workspace));
}
```

**Test All Paths**
```php
// Feature test covers: Happy Path + Error Paths + Authorization
public function test_user_can_create_workspace_happy_path(): void { /* ... */ }
public function test_validation_error_on_missing_name(): void { /* ... */ }
public function test_validation_error_on_duplicate_slug(): void { /* ... */ }
public function test_guest_cannot_create_workspace(): void { /* ... */ }
```

### TDD Test Organization

**Feature Tests** (test controller → request → model flow)
```
tests/Feature/
├── WorkspaceTest.php
├── MemberTest.php
└── SettingsTest.php
```

**Unit Tests** (test isolated logic)
```
tests/Unit/
├── Models/WorkspaceTest.php
└── Services/WorkspaceServiceTest.php
```

**Test Naming Convention**
```php
// Format: test_<scenario>_<expected_outcome>
test_user_can_create_workspace()
test_user_cannot_create_workspace_with_duplicate_slug()
test_guest_cannot_create_workspace()
test_workspace_slug_auto_generated_if_not_provided()
test_admin_can_delete_any_workspace()
test_owner_can_transfer_workspace_ownership()
```

### Common Mistakes to Avoid

| Mistake | Impact | Fix |
|---------|--------|-----|
| Writing implementation before test | Tests don't validate requirements | ALWAYS write test first, watch it fail |
| Mocking database in feature tests | Tests don't match production | Use real SQLite database (in-memory) |
| Testing implementation, not behavior | Refactors break tests unnecessarily | Test outcomes/behavior, not HOW it works |
| One giant test file | Hard to find/maintain tests | Organize by feature/story |
| Skipping error/edge case tests | Bugs reach production | Test happy path + all error paths |
| Not running full test suite before PR | Regressions in other areas | Always `php artisan test --compact` |

---

## Browser Testing with Laravel Dusk (E2E)

### When to Use Browser Tests vs Feature Tests

| Test Type | When to Use | Example |
|-----------|------------|---------|
| **Unit Test** | Test isolated logic (no dependencies) | Slug generation, validation rules |
| **Feature Test** | HTTP request → response (database included) | Login endpoint, form submission, redirect |
| **Browser Test** | Full user journey with JavaScript (DOM, clicks, waits) | Modal interactions, flash notifications, Alpine.js behavior |

**Decision Tree**:
```
Can test with HTTP request alone?
├─ YES → Feature Test (fast, reliable)
└─ NO (needs JS/DOM/clicks)
   └─ Browser Test (slower, comprehensive)
```

### Browser Test Structure (Dusk)

Browser tests simulate real user interactions: clicking buttons, filling forms, waiting for elements.

**Setup**:
```bash
php artisan dusk:chrome-driver --detect  # Download ChromeDriver
chmod -R 0755 vendor/laravel/dusk/bin/
php artisan dusk  # Run all browser tests
php artisan dusk tests/Browser/LoginFlowTest.php  # Run single test
```

### Example: Login Flow Browser Test

```php
// tests/Browser/LoginFlowTest.php
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginFlowTest extends DuskTestCase
{
    public function test_user_can_login_via_browser(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            // 1. Navigate to login page
            $browser->visit('/auth/login')
                // 2. Verify page loaded
                ->assertSee('Entrar')
                // 3. Fill email input
                ->type('email', 'user@example.com')
                // 4. Fill password input
                ->type('password', 'password123')
                // 5. Check "Remember me" checkbox
                ->check('remember')
                // 6. Click submit button
                ->press('Entrar')
                // 7. Wait for redirect and page load
                ->waitForLocation('/dashboard', 5)
                // 8. Verify redirected to dashboard
                ->assertPathIs('/dashboard')
                // 9. Verify user name shown
                ->assertSee('Dashboard')
                // 10. Verify authenticated
                ->assertAuthenticatedAs($user);
        });
    }

    public function test_user_sees_validation_errors(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/login')
                // Submit with empty fields
                ->press('Entrar')
                // Wait for validation errors to appear
                ->waitForText('O campo email é obrigatório', 5)
                // Assert error messages visible
                ->assertSee('O campo email é obrigatório')
                ->assertSee('O campo password é obrigatório')
                // Verify NOT redirected
                ->assertPathIs('/auth/login');
        });
    }
}
```

### Browser Test Examples: Common Patterns

**Modal Interactions**:
```php
public function test_user_can_open_and_close_modal(): void
{
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/dashboard')
            // Click button with dusk attribute
            ->click('@open-create-user-modal')
            // Wait for modal to appear
            ->waitForText('Criar Novo Usuário', 5)
            ->assertVisible('@user-modal')
            // Close modal via button
            ->click('@modal-close')
            // Wait for modal to disappear
            ->waitUntilMissing('@user-modal', 5)
            ->assertMissing('@user-modal');
    });
}
```

**Flash Notifications**:
```php
public function test_flash_notification_displays_after_action(): void
{
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/dashboard')
            // Perform action that triggers flash message
            ->click('@delete-button')
            ->click('@confirm-delete')
            // Wait for flash notification
            ->waitForText('Usuário deletado com sucesso', 5)
            // Verify notification styling (success = green)
            ->assertVisible('.fl-success')
            ->assertSee('Usuário deletado com sucesso');
    });
}
```

**Form Validation with Multiple Fields**:
```php
public function test_registration_form_validation(): void
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/auth/register')
            // Submit with invalid data
            ->type('name', '')
            ->type('email', 'invalid-email')
            ->type('password', '123')
            ->press('Registrar')
            // Verify all validation errors appear
            ->waitForText('O campo name é obrigatório', 5)
            ->assertSee('O campo name é obrigatório')
            ->assertSee('O campo email deve ser um endereço de email válido')
            ->assertSee('O password deve ter pelo menos 8 caracteres')
            ->assertPathIs('/auth/register');
    });
}
```

**Wait for Asynchronous Elements (Alpine.js)**:
```php
public function test_dropdown_menu_appears_with_javascript(): void
{
    $user = User::factory()->create();

    $this->browse(function (Browser $browser) use ($user) {
        $browser->loginAs($user)
            ->visit('/dashboard')
            // Click to open dropdown (Alpine.js toggle)
            ->click('@user-menu-toggle')
            // Wait for dropdown to become visible
            ->waitForText('Configurações', 5)
            ->assertVisible('@user-menu-dropdown')
            // Click menu item
            ->click('@user-menu-logout')
            // Wait for redirect
            ->waitForLocation('/auth/login', 5)
            ->assertAuthenticatedAs(null);
    });
}
```

### Dusk Helper Methods (Key API)

| Method | Purpose |
|--------|---------|
| `visit($url)` | Navigate to URL |
| `press($buttonText)` | Click button by text |
| `click($selector)` | Click element by CSS selector |
| `type($field, $value)` | Fill input field |
| `check($field)` | Check checkbox |
| `uncheck($field)` | Uncheck checkbox |
| `select($field, $value)` | Select option from dropdown |
| `waitForText($text, $seconds)` | Wait for text to appear |
| `waitUntilMissing($selector, $seconds)` | Wait for element to disappear |
| `waitForLocation($path, $seconds)` | Wait for URL change |
| `assertSee($text)` | Assert text visible |
| `assertMissing($text)` | Assert text NOT visible |
| `assertVisible($selector)` | Assert element visible |
| `assertMissing($selector)` | Assert element NOT visible |
| `assertPathIs($path)` | Assert current URL |
| `assertAuthenticatedAs($user)` | Assert user logged in |
| `loginAs($user)` | Login as user (bypasses form) |

### Using Dusk Attributes for Reliable Selectors

Instead of CSS selectors that break easily, use `dusk` attributes:

```blade
<!-- resources/views/settings/partials/users.blade.php -->
<x-button class="px-4" dusk="open-create-user-modal">
    Novo Usuário
</x-button>

<x-button variant="destructive" dusk="delete-button">
    Deletar
</x-button>

<x-button type="submit" variant="destructive" dusk="modal-confirm-delete">
    Confirmar
</x-button>
```

**In browser tests**, reference via `@` prefix:
```php
$browser->click('@open-create-user-modal')
        ->assertVisible('@user-modal')
        ->click('@modal-confirm-delete');
```

### Browser Test Limitations & Trade-offs

| Aspect | Browser Test | Feature Test |
|--------|--------------|--------------|
| **Speed** | Slow (5-10s per test) | Fast (<100ms per test) |
| **Coverage** | Full user journey | HTTP layer only |
| **Reliability** | Flakier (waits, timing) | Very reliable |
| **Best For** | UI flows, JavaScript | Business logic, APIs |
| **Cost** | High (selenium overhead) | Low |

**Strategy**: Test critical user journeys (login, checkout, admin actions) with browser tests. Test everything else with feature tests.

### Running Tests Locally & CI/CD

**Local Development**:
```bash
# Run feature tests (fast feedback)
php artisan test --compact

# Run browser tests (slower, when needed)
php artisan dusk
```

**CI/CD Pipeline** (.github/workflows/ci.yaml):
```yaml
- name: Download ChromeDriver
  run: php artisan dusk:chrome-driver --detect
  
- name: Run Browser Tests (Dusk)
  run: php artisan dusk
  
- name: Upload Screenshots on Failure
  if: failure()
  uses: actions/upload-artifact@v3
  with:
    name: dusk-screenshots
    path: tests/Browser/screenshots/
```

---

## Architecture Standards

### Backend Stack

- **Language**: PHP 8.4.1+ (PHP 8.2+ minimum)
- **Framework**: Laravel 12
- **Database**: SQLite (dev), PostgreSQL (prod)
- **Auth**: Custom controllers (LoginController, RegisterController, etc.) with session-based auth
- **Authorization**: Gates + Policies (no role checks in controllers)
- **Validation**: Form Request classes with array-based rules
- **ORM**: Eloquent exclusively; no raw queries unless unavoidable
- **Deployment**: Docker multi-stage build + Laravel Octane/Swoole
- **Task Queue**: Database driver (dev/test), Redis (prod optional)

### Frontend Stack

- **Templating**: Blade components
- **Styling**: Tailwind CSS v4 (no Bootstrap, no custom CSS frameworks)
- **Interactivity**: Alpine.js v3 (lightweight, directive-based)
- **Icons**: Lucide icons via `blade-lucide-icons`
- **Asset Bundling**: Vite 7
- **Font**: Lato (Google Fonts)
- **Notifications**: PHPFlasher with Noty adapter

### Database Design

- **Migrations**: Use Laravel Schema Builder; every alteration reversible
- **Models**: Use casts for JSON/enum fields; relationships use proper return types
- **Indexes**: Foreign keys, unique columns, frequently queried fields
- **Soft Deletes**: Use `softDeletes()` for audit trails (admins can restore)
- **Global Scopes**: Applied via traits (e.g., `BelongsToWorkspace`)

---

## Development Workflow

### Feature Development

1. **Create Feature Branch**: `git checkout -b <type>/<short-description>`
2. **Write Tests First**: Create `.php` test files BEFORE implementation
3. **Implement Feature**: Follow convention-based code structure
4. **Run Tests**: `php artisan test --compact` passes 100%
5. **Format & Lint**: Run `vendor/bin/pint && vendor/bin/phpstan analyse`
6. **Create PR**: Include test coverage, link to any tracking issue
7. **Review & Merge**: Approval required; all checks passing

### Code Organization

**Controllers**: MUST use Form Requests for validation, never inline validation
```php
public function store(CreateWorkspaceRequest $request): RedirectResponse
{
    $workspace = Workspace::create($request->validated());
    return redirect()->route('workspace.show', $workspace)->with('success', '...');
}
```

**Models**: MUST use explicit return types, relationship methods, casts
```php
public function workspace(): BelongsTo
{
    return $this->belongsTo(Workspace::class);
}

protected function casts(): array
{
    return ['briefing' => 'array', 'status' => WorkspaceStatus::class];
}
```

**Policies**: All authorization logic in Policy classes, none in controllers
```php
public function update(User $user, Workspace $workspace): bool
{
    return $user->roleIn($workspace) === WorkspaceRole::Owner;
}
```

### Testing Requirements

**Feature Tests** (default): Use real database, test full request/response cycle
```php
public function test_user_can_create_workspace(): void
{
    $user = User::factory()->create();
    $response = $this->actingAs($user)->post('/workspace', ['name' => '...']);
    $this->assertDatabaseHas('workspaces', ['name' => '...']);
}
```

**Unit Tests** (with `--unit`): Test isolated logic, can use mocks
```php
public function test_workspace_slug_generated_from_name(): void
{
    $workspace = new Workspace(['name' => 'My Workspace']);
    $this->assertEquals('my-workspace', $workspace->slug);
}
```

**Factories**: Use factories for test data; check for custom states before manual setup
```php
$workspace = Workspace::factory()->create();
$user = User::factory()->admin()->create();
```

---

## Quality Gates

### Automated Checks (CI/CD)

| Gate | Tool | Command | Pass Criteria |
|------|------|---------|--------------|
| **Formatting** | Pint | `vendor/bin/pint --dirty` | No changes needed |
| **Static Analysis** | PHPStan | `./vendor/bin/phpstan analyse` | Level 3, zero errors |
| **Tests** | PHPUnit | `php artisan test --compact` | 100% pass rate |
| **Security** | Manual | Code review | No secrets, credentials exposed |
| **Migrations** | Validation | Reversible? | Up/down both work |

### Code Review Checklist

**Core Tests**:
- [ ] Tests added (happy path + error cases)
- [ ] Tests pass locally (`php artisan test --compact`)
- [ ] Feature tests use real database (not mocks)
- [ ] If feature involves user interactions (modals, JavaScript), browser tests added
- [ ] Browser tests (if any) pass locally (`php artisan dusk`)
- [ ] Dusk attributes added to interactive elements (`dusk="button-name"`)

**Code Quality**:
- [ ] Formatting passes (`vendor/bin/pint`)
- [ ] PHPStan passes (`./vendor/bin/phpstan analyse`)
- [ ] Authorization: Policy classes used, not role checks in controller
- [ ] Flash messages use correct keys (`success`, `error`, `warning`, `info`)
- [ ] DB queries optimized: eager loading used, N+1 prevented
- [ ] New tables indexed appropriately
- [ ] Comments/PHPDoc added for non-obvious logic
- [ ] Conventional naming: kebab-case components, camelCase methods, etc.

**UI/UX Features**:
- [ ] New Blade components use `dusk` attributes for testing
- [ ] Dark mode supported (if relevant)
- [ ] Responsive design verified (mobile + desktop)
- [ ] Accessibility considered (semantic HTML, ARIA labels)

---

## Governance

### Constitution Authority

This constitution supersedes all other guidance. In case of conflict:
1. Constitution (this document)
2. CLAUDE.md (project-specific guidance)
3. AGENTS.md (agent-specific patterns)
4. Pull request conventions

### Amendment Process

To amend the constitution:

1. **Propose** an amendment in a GitHub issue or pull request
2. **Justify** the change with rationale (why it improves the project)
3. **Gather Feedback** from team (minimum 1 approval)
4. **Update Constitution**: Version bump follows semantic versioning
   - **MAJOR**: Principle removed or fundamentally redefined
   - **MINOR**: New principle added or significant clarification
   - **PATCH**: Wording improvement, typo fix, non-semantic refinement
5. **Propagate** changes: Update CLAUDE.md, AGENTS.md, templates as needed
6. **Commit**: Message format: `docs: amend constitution to vX.Y.Z (principle changes)`

### Compliance Verification

- **Code Review**: Every PR verified against Quality Gates checklist
- **Regular Audits**: Quarterly review of PR history for constitution violations
- **Escalation**: Violations blocking merge; author + reviewer align on fix

### Development Guidance Reference

- **CLAUDE.md** (project-specific): Commands, architecture details, conventions
- **AGENTS.md** (agent patterns): AI-specific guidance, auth flow, testing patterns

**Version**: 1.3.0 | **Ratified**: 2026-03-21 | **Last Amended**: 2026-03-21
