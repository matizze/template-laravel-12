<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tenant\Enums\TenantRole;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;
use Tests\TestCase;

class DeleteAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_owner_can_delete_account(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create([
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
        ]);

        $member = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $member->id,
            'tenant_id' => $tenant->id,
            'role' => TenantRole::Member,
        ]);

        $response = $this->actingAs($member)
            ->delete(route('settings.account.destroy'), [
                'password' => 'password',
            ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', [
            'id' => $member->id,
        ]);
    }

    public function test_owner_cannot_delete_account(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create([
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($owner)
            ->delete(route('settings.account.destroy'), [
                'password' => 'password',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
        ]);
    }

    public function test_deleting_account_removes_memberships(): void
    {
        $owner = User::factory()->create();
        $tenant1 = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create([
            'user_id' => $owner->id,
            'tenant_id' => $tenant1->id,
        ]);

        $tenant2 = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create([
            'user_id' => $owner->id,
            'tenant_id' => $tenant2->id,
        ]);

        $member = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $member->id,
            'tenant_id' => $tenant1->id,
            'role' => TenantRole::Member,
        ]);
        TenantUser::factory()->create([
            'user_id' => $member->id,
            'tenant_id' => $tenant2->id,
            'role' => TenantRole::Admin,
        ]);

        $this->actingAs($member)
            ->delete(route('settings.account.destroy'), [
                'password' => 'password',
            ]);

        $this->assertDatabaseMissing('tenant_user', [
            'user_id' => $member->id,
        ]);
    }

    public function test_user_with_multiple_tenants_can_delete_if_not_owner(): void
    {
        $owner1 = User::factory()->create();
        $tenant1 = Tenant::factory()->create(['user_id' => $owner1->id]);
        TenantUser::factory()->owner()->create([
            'user_id' => $owner1->id,
            'tenant_id' => $tenant1->id,
        ]);

        $owner2 = User::factory()->create();
        $tenant2 = Tenant::factory()->create(['user_id' => $owner2->id]);
        TenantUser::factory()->owner()->create([
            'user_id' => $owner2->id,
            'tenant_id' => $tenant2->id,
        ]);

        $member = User::factory()->create();
        TenantUser::factory()->create([
            'user_id' => $member->id,
            'tenant_id' => $tenant1->id,
            'role' => TenantRole::Member,
        ]);
        TenantUser::factory()->create([
            'user_id' => $member->id,
            'tenant_id' => $tenant2->id,
            'role' => TenantRole::Admin,
        ]);

        $response = $this->actingAs($member)
            ->delete(route('settings.account.destroy'), [
                'password' => 'password',
            ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', [
            'id' => $member->id,
        ]);

        $this->assertDatabaseMissing('tenant_user', [
            'user_id' => $member->id,
        ]);
    }

    public function test_deleting_account_requires_ownership_transfer(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $owner->id]);
        TenantUser::factory()->owner()->create([
            'user_id' => $owner->id,
            'tenant_id' => $tenant->id,
        ]);

        $response = $this->actingAs($owner)
            ->delete(route('settings.account.destroy'), [
                'password' => 'password',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
        ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
        ]);
    }
}
