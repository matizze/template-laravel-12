<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RegisterFlowTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_register_and_land_on_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/register')
                ->type('#name', 'Novo Usuário')
                ->type('#email', 'novo@example.com')
                ->type('#password', 'password123')
                ->press('Cadastrar')
                ->waitForText('Criar tenant')
                ->assertPathIs('/onboarding')
                ->press('Criar tenant')
                ->waitForText('Dashboard')
                ->assertPathIs('/dashboard')
                ->assertSee('Novo Usuário');
        });
    }

    public function test_user_can_navigate_to_login_from_register(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/register')
                ->clickLink('Acessar conta')
                ->waitForText('Acesse o portal')
                ->assertPathIs('/auth/login');
        });
    }
}
