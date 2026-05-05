<?php

namespace Tests\Feature;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;
use Tests\TestCase;

class UserCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('tenant.users.create.store');
    }

    public function test_authorized_admin_creates_global_user_and_reset_email_is_dispatched(): void
    {
        Notification::fake();

        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Owner,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->post(route('tenant.users.create.store'), [
                'name' => 'João',
                'email' => 'joao@example.com',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'joao@example.com', 'name' => 'João']);

        $newUser = User::where('email', 'joao@example.com')->firstOrFail();
        $this->assertSame(0, TenantUser::where('user_id', $newUser->id)->count());

        Notification::assertSentTo($newUser, ResetPassword::class);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Owner,
        ]);
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($admin)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->post(route('tenant.users.create.store'), [
                'name' => 'X',
                'email' => 'taken@example.com',
            ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', 'taken@example.com')->count());
    }

    public function test_no_access_screen_renders_when_user_has_no_links_and_tenants_exist(): void
    {
        Tenant::factory()->create();
        $orphan = User::factory()->create();

        $response = $this->actingAs($orphan)->get(route('onboarding'));

        $response->assertOk();
        $response->assertSee('Aguardando acesso');
        $response->assertDontSee('Vamos criar o seu primeiro tenant');
    }

    public function test_bootstrap_screen_renders_when_no_tenants_exist(): void
    {
        $first = User::factory()->create();

        $response = $this->actingAs($first)->get(route('onboarding'));

        $response->assertOk();
        $response->assertSee('Vamos criar o seu primeiro tenant');
    }

    public function test_non_admin_cannot_create_user(): void
    {
        $tenant = Tenant::factory()->create();
        $member = User::factory()->create(['role' => 'member']);
        TenantUser::create([
            'user_id' => $member->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $response = $this->actingAs($member)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->post(route('tenant.users.create.store'), [
                'name' => 'Forbidden',
                'email' => 'forbidden@example.com',
            ]);

        $response->assertForbidden();
        $this->assertSame(0, User::where('email', 'forbidden@example.com')->count());
    }

    public function test_user_creation_is_rate_limited(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        TenantUser::create([
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Owner,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($admin)
                ->withSession(['current_tenant_id' => $tenant->id])
                ->post(route('tenant.users.create.store'), [
                    'name' => "User $i",
                    'email' => "user$i@example.com",
                ]);
        }

        $sixth = $this->actingAs($admin)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->post(route('tenant.users.create.store'), [
                'name' => 'Sixth',
                'email' => 'sixth@example.com',
            ]);

        $sixth->assertStatus(429);
    }
}
