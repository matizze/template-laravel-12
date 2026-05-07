<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Permission\Database\Seeders\RoleSeeder;
use Modules\Permission\Models\Role;
use Modules\Tenant\Models\Tenant;
use Modules\User\Models\User;
use Tests\TestCase;

class AuditingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tinker / artisan invocations skip audit by default; force on for tests.
        config(['audit.console' => true]);
        $this->seed(RoleSeeder::class);
    }

    public function test_user_update_writes_an_audit_record(): void
    {
        $user = User::factory()->create(['name' => 'Original']);

        $user->update(['name' => 'Renamed']);

        $audit = DB::table('audits')
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame(['name' => 'Original'], json_decode($audit->old_values, true));
        $this->assertSame(['name' => 'Renamed'], json_decode($audit->new_values, true));
    }

    public function test_user_audit_never_records_password(): void
    {
        $user = User::factory()->create();

        $user->update(['password' => 'a-new-secret-pwd']);

        $audit = DB::table('audits')
            ->where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->latest('id')
            ->first();

        // The update payload only had `password` which is in $auditExclude;
        // the resulting audit (if any) must not contain it.
        if ($audit !== null) {
            $this->assertArrayNotHasKey('password', json_decode($audit->old_values, true) ?? []);
            $this->assertArrayNotHasKey('password', json_decode($audit->new_values, true) ?? []);
        }
    }

    public function test_role_permissions_change_is_audited(): void
    {
        $role = Role::where('name', 'admin')
            ->whereNull('tenant_id')
            ->firstOrFail();

        $role->update(['permissions' => ['users' => ['create']]]);

        $audit = DB::table('audits')
            ->where('auditable_type', Role::class)
            ->where('auditable_id', $role->id)
            ->where('event', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit, 'permissions changes on Role must be audited');
        $this->assertArrayHasKey('permissions', json_decode($audit->new_values, true));
    }

    public function test_tenant_creation_is_audited(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Acme']);

        $audit = DB::table('audits')
            ->where('auditable_type', Tenant::class)
            ->where('auditable_id', $tenant->id)
            ->where('event', 'created')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $new = json_decode($audit->new_values, true);
        $this->assertSame('Acme', $new['name']);
    }
}
