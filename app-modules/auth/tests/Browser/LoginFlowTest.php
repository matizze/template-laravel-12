<?php

namespace Tests\Browser;

use App\Enums\WorkspaceRole;
use App\Models\Member;
use Modules\User\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginFlowTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_login_and_see_dashboard(): void
    {
        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'password123',
        ]);
        $workspace = Workspace::factory()->for($user, 'owner')->create();
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/login')
                ->type('#email', 'joao@example.com')
                ->type('#password', 'password123')
                ->press('Entrar')
                ->waitForText('Dashboard')
                ->assertPathIs('/dashboard')
                ->assertSee('João Silva');
        });
    }

    public function test_user_can_login_with_remember_me(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        $workspace = Workspace::factory()->for($user, 'owner')->create();
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/login')
                ->type('#email', 'test@example.com')
                ->type('#password', 'password123')
                ->check('remember')
                ->press('Entrar')
                ->waitForText('Dashboard')
                ->assertPathIs('/dashboard');
        });
    }

    public function test_user_can_navigate_to_forgot_password(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/login')
                ->clickLink('Esqueceu a senha?')
                ->waitForText('Esqueceu sua senha?')
                ->assertPathIs('/auth/forgot-password')
                ->assertSee('Informe seu e-mail para receber um link de redefinição');
        });
    }

    public function test_user_can_navigate_to_register(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/auth/login')
                ->clickLink('Criar conta')
                ->waitForText('Crie sua conta')
                ->assertPathIs('/auth/register');
        });
    }
}
