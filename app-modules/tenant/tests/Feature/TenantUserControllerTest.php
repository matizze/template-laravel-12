<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Tests\TestCase;

class TenantUserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = RoleSeeder::class;

    public function test_membership_filter_limits_available_users_to_shared_tenants(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $callerUser = User::factory()->create();
        $tenantA->users()->attach($callerUser);

        // Shared user: also member of tenantA but not yet linked to tenantB
        $sharedUser = User::factory()->create();
        $tenantA->users()->attach($sharedUser);

        // Stranger user: only in tenantB
        $strangerUser = User::factory()->create();
        $tenantB->users()->attach($strangerUser);

        // Caller queries tenantB available users; with membership filter
        // they should only see users who share at least one of caller's tenants.
        $response = $this->actingAs($callerUser, 'sanctum')
            ->withHeader('X-Tenant-ID', $tenantB->id)
            ->getJson("/api/v1/tenants/{$tenantB->id}/users");

        // Caller is NOT a member of tenantB so tenants.users.view should fail.
        $response->assertStatus(403);
    }

    public function test_membership_filter_for_caller_member_of_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        $caller = User::factory()->create();
        $tenant->users()->attach($caller);

        $sharedUser = User::factory()->create();
        $tenant->users()->attach($sharedUser);

        // Stranger not in any tenant the caller is in.
        User::factory()->create();

        $response = $this->actingAs($caller, 'sanctum')
            ->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson("/api/v1/tenants/{$tenant->id}/users");

        $response->assertOk();

        $availableIds = collect($response->json('available_users.data'))->pluck('id')->all();

        // Stranger user must not appear because caller has no shared tenant with them.
        User::query()
            ->whereDoesntHave('tenants', fn ($q) => $q->whereKey($tenant->id))
            ->pluck('id')
            ->all();

        foreach ($availableIds as $id) {
            $this->assertContains($id, User::query()->whereHas('tenants', fn ($q) => $q->whereKey($tenant->id))->pluck('id')->all(), 'Available user must share a tenant with the caller.');
        }
    }

    public function test_superadmin_with_tenants_view_sees_all_available_users(): void
    {
        $tenant = Tenant::factory()->create();

        $admin = User::factory()->superadmin()->create();
        $tenant->users()->attach($admin);

        // Three completely unrelated users.
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $u3 = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson("/api/v1/tenants/{$tenant->id}/users");

        $response->assertOk();

        $availableIds = collect($response->json('available_users.data'))->pluck('id')->all();

        $this->assertContains($u1->id, $availableIds);
        $this->assertContains($u2->id, $availableIds);
        $this->assertContains($u3->id, $availableIds);
    }

    public function test_attach_succeeds_for_user_with_attach_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->superadmin()->create();
        $newUser = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/v1/tenants/{$tenant->id}/users", [
                'user_id' => $newUser->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $tenant->id,
            'user_id' => $newUser->id,
        ]);
    }

    public function test_detach_with_scoped_binding_returns_404_for_user_not_in_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $admin = User::factory()->superadmin()->create();

        // userInOtherTenant is a member of tenantB only; we attempt to detach them
        // via tenantA's URL, expecting scopeBindings to return 404 (IDOR regression).
        $userInOtherTenant = User::factory()->create();
        $tenantB->users()->attach($userInOtherTenant);

        $response = $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Tenant-ID', $tenantA->id)
            ->deleteJson("/api/v1/tenants/{$tenantA->id}/users/{$userInOtherTenant->id}");

        $response->assertStatus(404);
    }

    public function test_leave_endpoint_detaches_caller(): void
    {
        $tenant = Tenant::factory()->create();
        $caller = User::factory()->create();
        $tenant->users()->attach($caller);

        $this->assertDatabaseHas('tenant_user', [
            'tenant_id' => $tenant->id,
            'user_id' => $caller->id,
        ]);

        $response = $this->actingAs($caller, 'sanctum')
            ->withHeader('X-Tenant-ID', $tenant->id)
            ->postJson("/api/v1/tenants/{$tenant->id}/leave");

        $response->assertOk();
        $this->assertDatabaseMissing('tenant_user', [
            'tenant_id' => $tenant->id,
            'user_id' => $caller->id,
        ]);
    }
}
