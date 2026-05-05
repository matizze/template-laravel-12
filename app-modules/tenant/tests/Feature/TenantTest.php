<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_tenant(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('tenant.store'), [
                'name' => 'Meu Tenant',
                'description' => 'Descrição do tenant',
            ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('tenants', [
            'name' => 'Meu Tenant',
            'slug' => 'meu-tenant',
            'user_id' => $user->id,
            'description' => 'Descrição do tenant',
        ]);

        $this->assertDatabaseHas('tenant_user', [
            'user_id' => $user->id,
            'role' => TenantRole::Owner->value,
        ]);
    }

    public function test_tenant_slug_is_generated_if_not_provided(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tenant.store'), [
                'name' => 'Novo Projeto Legal',
            ]);

        $this->assertDatabaseHas('tenants', [
            'slug' => 'novo-projeto-legal',
        ]);
    }

    public function test_tenant_creation_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('tenant.store'), [
                'description' => 'Apenas descrição',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_tenant_slug_must_be_unique(): void
    {
        $user = User::factory()->create();

        Tenant::factory()->create(['slug' => 'meu-tenant', 'user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->post(route('tenant.store'), [
                'name' => 'Meu Tenant',
                'slug' => 'meu-tenant',
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_tenant_name_has_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('tenant.store'), [
                'name' => str_repeat('a', 256),
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_user_is_switched_to_new_tenant_after_creation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('tenant.store'), [
                'name' => 'Tenant Novo',
            ]);

        $tenant = Tenant::where('name', 'Tenant Novo')->first();

        $this->assertEquals($tenant->id, session('current_tenant_id'));
    }

    public function test_guest_cannot_create_tenant(): void
    {
        $response = $this->post(route('tenant.store'), [
            'name' => 'Tenant',
        ]);

        $response->assertRedirect('/auth/login');
    }

    public function test_user_can_switch_between_tenants(): void
    {
        $user = User::factory()->create();
        $tenant1 = Tenant::factory()->create(['user_id' => $user->id]);
        $tenant2 = Tenant::factory()->create(['user_id' => $user->id]);

        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant1->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant2->id]);

        $response = $this->actingAs($user)
            ->post(route('tenant.switch', $tenant2));

        $response->assertRedirect();
        $this->assertEquals($tenant2->id, session('current_tenant_id'));
    }

    public function test_owner_can_update_tenant_name(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $user->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)
            ->patch(route('tenant.settings.update', $tenant), [
                'name' => 'Novo Nome',
                'slug' => $tenant->slug,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Novo Nome',
        ]);
    }

    public function test_owner_can_update_tenant_description(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $user->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant->id]);

        $response = $this->actingAs($user)
            ->patch(route('tenant.settings.update', $tenant), [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'description' => 'Nova descrição do tenant',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'description' => 'Nova descrição do tenant',
        ]);
    }

    public function test_tenant_slug_validation_on_update(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $user->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant->id]);

        Tenant::factory()->create(['slug' => 'slug-existente']);

        $response = $this->actingAs($user)
            ->patch(route('tenant.settings.update', $tenant), [
                'name' => $tenant->name,
                'slug' => 'slug-existente',
            ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_member_cannot_update_tenant(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $member = User::factory()->create();
        TenantUser::factory()->create(['user_id' => $member->id, 'tenant_id' => $tenant->id, 'role' => TenantRole::Member]);

        $response = $this->actingAs($member)
            ->patch(route('tenant.settings.update', $tenant), [
                'name' => 'Tentativa de Alteração',
                'slug' => $tenant->slug,
            ]);

        $response->assertForbidden();
    }

    public function test_owner_can_delete_tenant(): void
    {
        $user = User::factory()->create();
        $activeTenant = Tenant::factory()->create(['user_id' => $user->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $activeTenant->id]);

        $tenant = Tenant::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->delete(route('tenant.destroy', $tenant));

        $response->assertRedirect(route('dashboard'));

        $this->assertSoftDeleted('tenants', [
            'id' => $tenant->id,
        ]);
    }

    public function test_member_cannot_delete_tenant(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $member = User::factory()->create();
        TenantUser::factory()->create(['user_id' => $member->id, 'tenant_id' => $tenant->id, 'role' => TenantRole::Member]);

        $response = $this->actingAs($member)
            ->delete(route('tenant.destroy', $tenant));

        $response->assertForbidden();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
        ]);
    }

    public function test_owner_can_transfer_ownership(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $newOwner = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $newOwner->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('tenant.transferOwnership', $tenant), [
                'user_id' => $newOwner->id,
            ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('tenant_user', [
            'user_id' => $newOwner->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Owner->value,
        ]);
    }

    public function test_ownership_transfer_demotes_old_owner(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $newOwner = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $newOwner->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $this->actingAs($owner)
            ->post(route('tenant.transferOwnership', $tenant), [
                'user_id' => $newOwner->id,
            ]);

        $this->assertDatabaseHas('tenant_user', [
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Admin->value,
        ]);
    }

    public function test_ownership_transfer_promotes_new_owner(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $newOwner = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $newOwner->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Admin,
        ]);

        $this->actingAs($owner)
            ->post(route('tenant.transferOwnership', $tenant), [
                'user_id' => $newOwner->id,
            ]);

        $this->assertDatabaseHas('tenant_user', [
            'user_id' => $newOwner->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Owner->value,
        ]);

        $tenant->refresh();
        $this->assertEquals($newOwner->id, $tenant->user_id);
    }

    public function test_cannot_transfer_to_non_member(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $nonMember = User::factory()->create();

        $response = $this->actingAs($owner)
            ->post(route('tenant.transferOwnership', $tenant), [
                'user_id' => $nonMember->id,
            ]);

        $response->assertSessionHasErrors('user_id');

        $this->assertDatabaseHas('tenant_user', [
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Owner->value,
        ]);
    }

    public function test_admin_cannot_transfer_ownership(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $admin = User::factory()->create();
        TenantUser::factory()->admin()->create([
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
        ]);

        $anotherMember = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $anotherMember->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('tenant.transferOwnership', $tenant), [
                'user_id' => $anotherMember->id,
            ]);

        $response->assertForbidden();
    }

    public function test_member_cannot_transfer_ownership(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $member = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $member->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $response = $this->actingAs($member)
            ->post(route('tenant.transferOwnership', $tenant), [
                'user_id' => $owner->id,
            ]);

        $response->assertForbidden();
    }

    public function test_owner_can_update_member_role_via_patch(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $member = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $member->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->patch(route('tenant.users.updateRole', [$tenant, $member]), [
                'role' => TenantRole::Admin->value,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_user', [
            'user_id' => $member->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Admin->value,
        ]);
    }

    public function test_admin_can_update_member_role_via_patch(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $admin = User::factory()->create();
        TenantUser::factory()->admin()->create([
            'user_id' => $admin->id,
            'tenant_id' => $tenant->id,
        ]);

        $member = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $member->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('tenant.users.updateRole', [$tenant, $member]), [
                'role' => TenantRole::Viewer->value,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenant_user', [
            'user_id' => $member->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Viewer->value,
        ]);
    }

    public function test_member_without_permission_receives_403_on_role_update(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $member = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $member->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $anotherMember = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $anotherMember->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $response = $this->actingAs($member)
            ->patch(route('tenant.users.updateRole', [$tenant, $anotherMember]), [
                'role' => TenantRole::Admin->value,
            ]);

        $response->assertForbidden();
    }

    public function test_put_request_to_update_role_returns_405(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create(['user_id' => $owner->id, 'tenant_id' => $tenant->id]);

        $member = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $member->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $response = $this->actingAs($owner)
            ->put(route('tenant.users.updateRole', [$tenant, $member]), [
                'role' => TenantRole::Admin->value,
            ]);

        $response->assertStatus(405);
    }
}
