<?php

namespace Tests\Browser;

use Modules\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ModalTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_delete_account_modal_opens_and_closes(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings?tab=profile')
                ->waitForText('Deletar conta')
                ->click('@open-delete-modal')
                ->waitForText('Tem certeza que deseja deletar sua conta?')
                ->assertSee('Tem certeza que deseja deletar sua conta?')
                ->press('Cancelar')
                ->waitUntilMissingText('Tem certeza que deseja deletar sua conta?')
                ->assertDontSee('Tem certeza que deseja deletar sua conta?');
        });
    }

    public function test_delete_account_modal_closes_on_escape(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings?tab=profile')
                ->waitForText('Deletar conta')
                ->click('@open-delete-modal')
                ->waitForText('Tem certeza que deseja deletar sua conta?')
                ->keys('', '{escape}')
                ->waitUntilMissingText('Tem certeza que deseja deletar sua conta?')
                ->assertDontSee('Tem certeza que deseja deletar sua conta?');
        });
    }

    public function test_delete_account_modal_submits_with_password(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings?tab=profile')
                ->waitForText('Deletar conta')
                ->click('@open-delete-modal')
                ->waitForText('Tem certeza que deseja deletar sua conta?')
                ->type('#password', 'password123')
                ->press('@modal-confirm-delete')
                ->waitForLocation('/auth/login')
                ->assertPathIs('/auth/login');
        });
    }

    public function test_create_user_modal_opens_and_closes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/settings?tab=users')
                ->waitForText('Gerencie os usuários do sistema')
                ->click('@open-create-user-modal')
                ->waitFor('form[action*="users"]')
                ->assertSee('Cancelar')
                ->press('Cancelar')
                ->pause(300)
                ->assertDontSee('Cancelar');
        });
    }
}
