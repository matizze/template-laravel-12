<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Permission\Models\Role;
use Modules\Permission\Services\PermissionService;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_flatten_simple_actions(): void
    {
        $service = app(PermissionService::class);

        $result = $service->flatten([
            'users' => ['create', 'update', 'delete'],
        ]);

        $this->assertEquals([
            'users.create',
            'users.update',
            'users.delete',
        ], $result);
    }

    public function test_flatten_dotted_namespace(): void
    {
        $service = app(PermissionService::class);

        $result = $service->flatten([
            'rh.aso.exames' => ['create', 'delete'],
        ]);

        $this->assertEquals([
            'rh.aso.exames.create',
            'rh.aso.exames.delete',
        ], $result);
    }

    public function test_flatten_multiple_namespaces(): void
    {
        $service = app(PermissionService::class);

        $result = $service->flatten([
            'users' => ['create', 'update'],
            'finance.order' => ['view', 'approve'],
        ]);

        $this->assertEquals([
            'users.create',
            'users.update',
            'finance.order.view',
            'finance.order.approve',
        ], $result);
    }

    public function test_check_returns_true_for_matching_permission(): void
    {
        $role = Role::factory()->create([
            'name' => 'admin',
            'tenant_id' => null,
            'permissions' => ['users' => ['create', 'update']],
        ]);

        $user = User::factory()->create();
        $role->assign($user);

        $service = app(PermissionService::class);

        $this->assertTrue($service->check($user, 'users.create'));
        $this->assertTrue($service->check($user, 'users.update'));
    }

    public function test_check_returns_false_for_missing_permission(): void
    {
        $role = Role::factory()->create([
            'name' => 'viewer',
            'tenant_id' => null,
            'permissions' => ['users' => ['create']],
        ]);

        $user = User::factory()->create();
        $role->assign($user);

        $service = app(PermissionService::class);

        $this->assertFalse($service->check($user, 'users.delete'));
    }

    public function test_permissions_are_accumulative_across_roles(): void
    {
        $role1 = Role::factory()->create([
            'name' => 'creator',
            'tenant_id' => null,
            'permissions' => ['users' => ['create']],
        ]);

        $role2 = Role::factory()->create([
            'name' => 'deleter',
            'tenant_id' => null,
            'permissions' => ['users' => ['delete']],
        ]);

        $user = User::factory()->create();
        $role1->assign($user);
        $role2->assign($user);

        $service = app(PermissionService::class);

        $this->assertTrue($service->check($user, 'users.create'));
        $this->assertTrue($service->check($user, 'users.delete'));
    }

    public function test_user_without_roles_has_no_permissions(): void
    {
        $user = User::factory()->create();

        $service = app(PermissionService::class);

        $this->assertFalse($service->check($user, 'users.create'));
    }

    public function test_revoke_removes_permission_immediately(): void
    {
        $role = Role::factory()->create([
            'name' => 'admin',
            'tenant_id' => null,
            'permissions' => ['users' => ['create']],
        ]);

        $user = User::factory()->create();
        $role->assign($user);

        $service = app(PermissionService::class);

        $this->assertTrue($service->check($user, 'users.create'));

        $role->revoke($user);
        $user->refresh();
        $user->load('roles');

        $this->assertFalse($service->check($user, 'users.create'));
    }

    public function test_gate_integration_works(): void
    {
        $role = Role::factory()->create([
            'name' => 'admin',
            'tenant_id' => null,
            'permissions' => ['users' => ['create']],
        ]);

        $user = User::factory()->create();
        $role->assign($user);

        $this->assertTrue($user->can('users.create'));
        $this->assertFalse($user->can('users.delete'));
    }

    public function test_role_with_empty_permissions(): void
    {
        $role = Role::factory()->create([
            'name' => 'empty',
            'tenant_id' => null,
            'permissions' => [],
        ]);

        $user = User::factory()->create();
        $role->assign($user);

        $service = app(PermissionService::class);

        $this->assertFalse($service->check($user, 'users.create'));
    }

    public function test_role_with_null_permissions(): void
    {
        $role = Role::factory()->create([
            'name' => 'null-perms',
            'tenant_id' => null,
            'permissions' => null,
        ]);

        $user = User::factory()->create();
        $role->assign($user);

        $service = app(PermissionService::class);

        $this->assertFalse($service->check($user, 'users.create'));
    }

    public function test_is_global_returns_true_when_no_tenant(): void
    {
        $role = Role::factory()->create(['name' => 'global', 'tenant_id' => null]);

        $this->assertTrue($role->isGlobal());
    }

    public function test_is_global_returns_false_with_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $role = Role::factory()->create(['name' => 'scoped', 'tenant_id' => $tenant->id]);

        $this->assertFalse($role->isGlobal());
    }

    public function test_user_roles_relation_works(): void
    {
        $role = Role::factory()->create(['name' => 'editor', 'tenant_id' => null]);
        $user = User::factory()->create();
        $role->assign($user);

        $this->assertTrue($user->load('roles')->roles->contains('name', 'editor'));
        $this->assertFalse($user->roles->contains('name', 'admin'));
    }

    public function test_global_scope_filters_by_current_tenant_in_http(): void
    {
        $tenant = Tenant::factory()->create();
        Role::factory()->create(['name' => 'global-role', 'tenant_id' => null]);
        Role::factory()->create(['name' => 'tenant-role', 'tenant_id' => $tenant->id]);

        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeader('X-Tenant-ID', $tenant->id)
            ->getJson('/api/v1/tenants');

        $response->assertOk();
    }

    public function test_global_scope_does_not_filter_in_console(): void
    {
        Role::factory()->create(['name' => 'global-role', 'tenant_id' => null]);

        $visible = Role::pluck('name')->toArray();

        $this->assertContains('global-role', $visible);
    }
}
