<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Policies\TenantPolicy;
use Modules\User\Models\User;
use Tests\TestCase;

class TenantPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = RoleSeeder::class;

    private TenantPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TenantPolicy;
    }

    public function test_view_requires_membership_of_the_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $member = User::factory()->create();
        $tenant->users()->attach($member);

        $stranger = User::factory()->create();

        $this->assertTrue($this->policy->view($member, $tenant));
        $this->assertFalse($this->policy->view($stranger, $tenant));
    }

    public function test_update_returns_false_for_member_without_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $member = User::factory()->create();
        $tenant->users()->attach($member);

        $this->assertFalse($this->policy->update($member, $tenant));
    }

    public function test_delete_returns_false_for_member_without_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $member = User::factory()->create();
        $tenant->users()->attach($member);

        $this->assertFalse($this->policy->delete($member, $tenant));
    }

    public function test_restore_returns_false_for_member_without_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $member = User::factory()->create();
        $tenant->users()->attach($member);
        $tenant->delete();

        $this->assertFalse($this->policy->restore($member->fresh(), $tenant->fresh()));
    }

    public function test_update_returns_true_for_user_with_settings_update_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->superadmin()->create();

        $this->assertTrue($this->policy->update($admin, $tenant));
    }

    public function test_delete_returns_true_for_user_with_settings_delete_permission(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->superadmin()->create();

        $this->assertTrue($this->policy->delete($admin, $tenant));
    }

    public function test_restore_returns_true_for_user_with_settings_update_permission_when_trashed(): void
    {
        $tenant = Tenant::factory()->create();
        $tenant->delete();
        $admin = User::factory()->superadmin()->create();

        $this->assertTrue($this->policy->restore($admin, $tenant));
    }

    public function test_update_returns_false_on_trashed_tenant_even_for_admin(): void
    {
        $tenant = Tenant::factory()->create();
        $tenant->delete();
        $admin = User::factory()->superadmin()->create();

        $this->assertFalse($this->policy->update($admin, $tenant));
    }

    public function test_delete_returns_false_on_trashed_tenant_even_for_admin(): void
    {
        $tenant = Tenant::factory()->create();
        $tenant->delete();
        $admin = User::factory()->superadmin()->create();

        $this->assertFalse($this->policy->delete($admin, $tenant));
    }

    public function test_restore_returns_false_on_active_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->superadmin()->create();

        $this->assertFalse($this->policy->restore($admin, $tenant));
    }
}
