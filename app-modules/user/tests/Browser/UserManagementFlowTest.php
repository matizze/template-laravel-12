<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Models\TenantUser;
use Modules\User\Models\User;
use Tests\DuskTestCase;

class UserManagementFlowTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_admin_can_create_user_via_modal(): void
    {
        $admin = User::factory()->admin()->create();
        $tenant = Tenant::factory()->for($admin, 'owner')->create();
        TenantUser::factory()->owner()->create(['user_id' => $admin->id, 'tenant_id' => $tenant->id]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/settings?tab=users')
                ->waitForText('Gerencie os usuários do sistema')
                ->click('@open-create-user-modal')
                ->waitFor('form[action*="users"]')
                ->type('#name', 'Novo Membro')
                ->type('#email', 'membro@example.com')
                ->type('#password', 'password123')
                ->type('#password_confirmation', 'password123')
                ->press('Salvar')
                ->waitForText('Usuário criado com sucesso')
                ->assertSee('Novo Membro')
                ->assertSee('membro@example.com');
        });
    }

    public function test_admin_can_toggle_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create([
            'name' => 'Test Member',
            'role' => 'member',
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/settings?tab=users')
                ->waitForText('Test Member')
                ->assertSee('Membro')
                ->click('button[title="Tornar Administrador"]')
                ->waitForText('Função atualizada com sucesso')
                ->assertSee('Administrador');
        });
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create([
            'name' => 'User To Delete',
            'email' => 'delete@example.com',
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/settings?tab=users')
                ->waitForText('User To Delete')
                ->click('button[title="Deletar"]')
                ->acceptDialog()
                ->waitForText('Usuário deletado com sucesso')
                ->assertDontSee('delete@example.com');
        });
    }

    public function test_users_table_shows_all_users(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin User']);
        User::factory()->create(['name' => 'Member One']);
        User::factory()->create(['name' => 'Member Two']);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/settings?tab=users')
                ->waitForText('Admin User')
                ->assertSee('Member One')
                ->assertSee('Member Two');
        });
    }
}
