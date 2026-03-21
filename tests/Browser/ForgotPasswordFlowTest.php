<?php

namespace Tests\Browser;

use Modules\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ForgotPasswordFlowTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_forgot_password_page_is_accessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/forgot-password')
                ->assertSee('Esqueceu sua senha?')
                ->assertSee('Informe seu e-mail para receber um link de redefinição');
        });
    }

    public function test_can_submit_forgot_password_form(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/forgot-password')
                ->type('#email', 'test@example.com')
                ->press('Enviar link')
                ->waitForText('receberá um link')
                ->assertSee('receberá um link');
        });
    }

    public function test_can_navigate_back_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/forgot-password')
                ->clickLink('Voltar ao login')
                ->waitForText('Acesse o portal')
                ->assertPathIs('/auth/login');
        });
    }

    public function test_reset_password_page_shows_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/reset-password/fake-token?email=test@example.com')
                ->assertSee('Redefinir senha')
                ->assertSee('Informe sua nova senha')
                ->assertInputValue('#email', 'test@example.com');
        });
    }
}
