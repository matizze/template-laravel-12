<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;
use Tests\DuskTestCase;

class TenantFlowTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_open_create_tenant_modal_via_switcher(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $tenant = Tenant::factory()->create(['user_id' => $user->id, 'name' => 'Tenant Teste']);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->waitForText('Dashboard')
                ->click('@tenant-switcher')
                ->waitFor('@open-create-tenant-link', 5)
                ->click('@open-create-tenant-link')
                ->waitForText('Criar Tenant');
        });
    }

    public function test_user_can_switch_tenant_via_ui(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $tenant1 = Tenant::factory()->create(['user_id' => $user->id, 'name' => 'Tenant A']);
        $tenant2 = Tenant::factory()->create(['user_id' => $user->id, 'name' => 'Tenant B']);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant1->id]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant2->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->waitForText('Dashboard')
                ->click('@tenant-switcher')
                ->waitForText('Tenant A')
                ->waitForText('Tenant B')
                ->assertSee('Tenant A')
                ->assertSee('Tenant B');
        });
    }

    public function test_user_can_update_tenant_settings_via_ui(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $tenant = Tenant::factory()->create([
            'user_id' => $user->id,
            'name' => 'Tenant Original',
            'slug' => 'tenant-original',
        ]);
        TenantUser::factory()->owner()->create(['user_id' => $user->id, 'tenant_id' => $tenant->id]);

        $this->browse(function (Browser $browser) use ($user, $tenant) {
            $browser->loginAs($user)
                ->visit("/tenant/{$tenant->id}/settings")
                ->waitForText('Tenant Original')
                ->clear('#name')
                ->type('#name', 'Tenant Atualizado')
                ->press('Salvar')
                ->waitForLocation("/tenant/{$tenant->id}/settings")
                ->assertSee('Tenant Atualizado');
        });

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Tenant Atualizado',
        ]);
    }
}
