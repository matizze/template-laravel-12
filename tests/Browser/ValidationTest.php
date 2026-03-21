<?php

namespace Tests\Browser;

use Modules\User\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ValidationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_login_shows_error_for_invalid_email_format(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/login')
                ->type('#email', 'not-an-email')
                ->type('#password', 'password123')
                ->script("document.querySelector('input[name=email]').type = 'text'");

            $browser->press('Entrar')
                ->waitFor('.text-red-500')
                ->assertSee('email');
        });
    }

    public function test_register_shows_error_for_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/register')
                ->type('#name', 'Test User')
                ->type('#email', 'taken@example.com')
                ->type('#password', 'password123')
                ->press('Cadastrar')
                ->waitFor('.text-red-500')
                ->assertSee('email');
        });
    }

    public function test_settings_profile_shows_error_for_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings?tab=profile')
                ->waitForText('Informações do perfil')
                ->clear('#email')
                ->type('#email', 'existing@example.com')
                ->press('Salvar')
                ->waitFor('.text-red-500')
                ->assertPresent('.text-red-500');
        });
    }

    public function test_forgot_password_shows_error_for_invalid_email(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/forgot-password')
                ->type('#email', 'not-an-email')
                ->script("document.querySelector('input[name=email]').type = 'text'");

            $browser->press('Enviar link')
                ->waitFor('.text-red-500')
                ->assertSee('email');
        });
    }
}
