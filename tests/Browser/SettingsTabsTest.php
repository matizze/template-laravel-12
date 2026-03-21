<?php

namespace Tests\Browser;

use App\Models\Member;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SettingsTabsTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_can_navigate_between_profile_and_password_tabs(): void
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings?tab=profile')
                ->waitForText('Informações do perfil')
                ->assertSee('Informações do perfil')
                ->clickLink('Redefinir Senha')
                ->waitForText('Atualizar senha')
                ->assertSee('Atualizar senha')
                ->assertQueryStringHas('tab', 'password')
                ->clickLink('Perfil')
                ->waitForText('Informações do perfil')
                ->assertSee('Informações do perfil')
                ->assertQueryStringHas('tab', 'profile');
        });
    }

    public function test_admin_can_see_users_tab(): void
    {
        $admin = User::factory()->admin()->create();

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/settings?tab=profile')
                ->waitForText('Informações do perfil')
                ->assertSeeLink('Usuários')
                ->clickLink('Usuários')
                ->waitForText('Gerencie os usuários do sistema')
                ->assertSee('Gerencie os usuários do sistema')
                ->assertQueryStringHas('tab', 'users');
        });
    }

    public function test_member_cannot_see_users_tab(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->browse(function (Browser $browser) use ($member) {
            $browser->loginAs($member)
                ->visit('/settings?tab=profile')
                ->waitForText('Informações do perfil')
                ->assertDontSeeLink('Usuários');
        });
    }

    public function test_profile_tab_shows_current_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
        ]);
        $workspace = Workspace::factory()->for($user, 'owner')->create();
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/settings?tab=profile')
                ->waitForText('Informações do perfil')
                ->assertInputValue('#name', 'Maria Silva')
                ->assertInputValue('#email', 'maria@example.com');
        });
    }
}
