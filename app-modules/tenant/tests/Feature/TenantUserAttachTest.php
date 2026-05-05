<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;
use Tests\TestCase;

class TenantUserAttachTest extends TestCase
{
    use RefreshDatabase;

    private function ownedTenant(User $owner): Tenant
    {
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::create([
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Owner,
        ]);

        return $tenant;
    }

    public function test_owner_attaches_existing_user_to_operable_tenant(): void
    {
        $owner = User::factory()->create();
        $tenant = $this->ownedTenant($owner);
        $newcomer = User::factory()->create();

        $response = $this->actingAs($owner)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->post(route('tenant.users.store', $tenant), [
                'user_id' => $newcomer->id,
                'role' => TenantRole::Member->value,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_user', [
            'user_id' => $newcomer->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member->value,
        ]);
    }

    public function test_user_in_two_tenants_independent_roles(): void
    {
        $owner = User::factory()->create();
        $a = $this->ownedTenant($owner);
        $b = $this->ownedTenant($owner);
        $multi = User::factory()->create();

        $this->actingAs($owner)->withSession(['current_tenant_id' => $a->id])
            ->post(route('tenant.users.store', $a), [
                'user_id' => $multi->id,
                'role' => TenantRole::Admin->value,
            ]);
        $this->actingAs($owner)->withSession(['current_tenant_id' => $b->id])
            ->post(route('tenant.users.store', $b), [
                'user_id' => $multi->id,
                'role' => TenantRole::Viewer->value,
            ]);

        $this->assertSame(2, TenantUser::where('user_id', $multi->id)->count());
        $this->assertDatabaseHas('tenant_user', [
            'user_id' => $multi->id,
            'tenant_id' => $a->id,
            'role' => TenantRole::Admin->value,
        ]);
        $this->assertDatabaseHas('tenant_user', [
            'user_id' => $multi->id,
            'tenant_id' => $b->id,
            'role' => TenantRole::Viewer->value,
        ]);
    }

    public function test_attach_to_grouper_is_rejected(): void
    {
        $owner = User::factory()->create();
        $matriz = $this->ownedTenant($owner);
        Tenant::factory()->create(['parent_id' => $matriz->id, 'user_id' => $owner->id]);
        $newcomer = User::factory()->create();

        $current = $this->ownedTenant($owner);

        $response = $this->actingAs($owner)
            ->withSession(['current_tenant_id' => $current->id])
            ->post(route('tenant.users.store', $matriz), [
                'user_id' => $newcomer->id,
                'role' => TenantRole::Member->value,
            ]);

        $response->assertSessionHasErrors('tenant');
        $this->assertDatabaseMissing('tenant_user', [
            'user_id' => $newcomer->id,
            'tenant_id' => $matriz->id,
        ]);
    }

    public function test_revoke_link_invalidates_active_session(): void
    {
        $owner = User::factory()->create();
        $a = $this->ownedTenant($owner);
        $member = User::factory()->create();
        TenantUser::create([
            'user_id' => $member->id,
            'tenant_id' => $a->id,
            'role' => TenantRole::Member,
        ]);

        $this->actingAs($member)->withSession(['current_tenant_id' => $a->id])
            ->get(route('dashboard'))->assertOk();

        $this->actingAs($owner)->withSession(['current_tenant_id' => $a->id])
            ->delete(route('tenant.users.remove', [$a, $member]))->assertRedirect();

        $this->actingAs($member)->withSession(['current_tenant_id' => $a->id])
            ->get(route('dashboard'))->assertRedirect(route('onboarding'));
    }

    public function test_cannot_remove_last_owner(): void
    {
        $owner = User::factory()->create();
        $tenant = $this->ownedTenant($owner);

        $response = $this->actingAs($owner)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->delete(route('tenant.users.remove', [$tenant, $owner]));

        $response->assertSessionHasErrors('user');
        $this->assertDatabaseHas('tenant_user', [
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_duplicate_attach_is_rejected(): void
    {
        $owner = User::factory()->create();
        $tenant = $this->ownedTenant($owner);
        $member = User::factory()->create();
        TenantUser::create([
            'user_id' => $member->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->withSession(['current_tenant_id' => $tenant->id])
            ->post(route('tenant.users.store', $tenant), [
                'user_id' => $member->id,
                'role' => TenantRole::Admin->value,
            ]);

        $response->assertSessionHasErrors('user_id');
    }
}
