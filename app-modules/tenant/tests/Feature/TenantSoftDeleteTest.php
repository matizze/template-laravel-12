<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;
use Tests\TestCase;

class TenantSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithActiveTenant(): User
    {
        $user = User::factory()->create();
        $active = Tenant::factory()->root()->create(['user_id' => $user->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $active->id]);

        return $user;
    }

    public function test_owner_can_delete_leaf_without_links(): void
    {
        $user = $this->createUserWithActiveTenant();

        $tenant = Tenant::factory()->root()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->delete(route('tenant.destroy', $tenant));

        $response->assertRedirect(route('dashboard'));
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    }

    public function test_cannot_delete_leaf_with_active_links(): void
    {
        $user = $this->createUserWithActiveTenant();

        $tenant = Tenant::factory()->root()->create(['user_id' => $user->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant->id]);

        $other = User::factory()->create();
        TenantUser::factory()->create(['user_id' => $other->id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)
            ->delete(route('tenant.destroy', $tenant));

        $response->assertSessionHasErrors('tenant');
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_grouper_tenant(): void
    {
        $user = $this->createUserWithActiveTenant();

        $grouper = Tenant::factory()->root()->create(['user_id' => $user->id]);
        Tenant::factory()->withParent($grouper)->create();

        $response = $this->actingAs($user)
            ->delete(route('tenant.destroy', $grouper));

        $response->assertSessionHasErrors('tenant');
        $this->assertDatabaseHas('tenants', ['id' => $grouper->id, 'deleted_at' => null]);
    }

    public function test_soft_deleted_tenants_are_excluded_from_listings(): void
    {
        $user = $this->createUserWithActiveTenant();

        $tenant = Tenant::factory()->root()->create(['user_id' => $user->id]);
        $tenant->delete();

        $this->assertNull(Tenant::find($tenant->id));
        $this->assertNotNull(Tenant::withTrashed()->find($tenant->id));
    }

    public function test_can_restore_tenant_when_parent_not_deleted(): void
    {
        $user = $this->createUserWithActiveTenant();

        $parent = Tenant::factory()->root()->create(['user_id' => $user->id]);
        $tenant = Tenant::factory()->withParent($parent)->create(['user_id' => $user->id]);
        $tenant->delete();

        $response = $this->actingAs($user)
            ->post(route('tenant.restore', $tenant->id));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'deleted_at' => null]);
    }

    public function test_cannot_restore_when_parent_is_soft_deleted(): void
    {
        $user = $this->createUserWithActiveTenant();

        $parent = Tenant::factory()->root()->create(['user_id' => $user->id]);
        $tenant = Tenant::factory()->withParent($parent)->create(['user_id' => $user->id]);

        $tenant->delete();
        $parent->delete();

        $response = $this->actingAs($user)
            ->post(route('tenant.restore', $tenant->id));

        $response->assertSessionHasErrors('tenant');
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    }

    public function test_can_restore_root_tenant(): void
    {
        $user = $this->createUserWithActiveTenant();

        $tenant = Tenant::factory()->root()->create(['user_id' => $user->id]);
        $tenant->delete();

        $response = $this->actingAs($user)
            ->post(route('tenant.restore', $tenant->id));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'deleted_at' => null]);
    }

    public function test_stranger_cannot_restore_someone_elses_tenant(): void
    {
        $owner = $this->createUserWithActiveTenant();
        $stranger = $this->createUserWithActiveTenant();

        $tenant = Tenant::factory()->root()->create(['user_id' => $owner->id]);
        $tenant->delete();

        $response = $this->actingAs($stranger)
            ->post(route('tenant.restore', $tenant->id));

        $response->assertForbidden();
        $this->assertSoftDeleted('tenants', ['id' => $tenant->id]);
    }
}
