<?php

namespace Tests\Browser;

use App\Models\Member;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class WorkspaceFlowTest extends DuskTestCase
{
    use DatabaseMigrations;

    // T114: test_user_can_navigate_to_create_workspace_via_switcher
    public function test_user_can_navigate_to_create_workspace_via_switcher(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $workspace = Workspace::factory()->create(['user_id' => $user->id, 'name' => 'Workspace Teste']);
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->waitForText('Dashboard')
                ->click('@workspace-switcher')
                ->waitFor('@open-create-workspace-link', 5)
                ->click('@open-create-workspace-link')
                ->waitForText('Criar Workspace')
                ->assertPathIs('/workspace/create')
                ->assertSee('Criar Workspace');
        });
    }

    // T115: test_user_can_switch_workspace_via_ui
    public function test_user_can_switch_workspace_via_ui(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $workspace1 = Workspace::factory()->create(['user_id' => $user->id, 'name' => 'Workspace A']);
        $workspace2 = Workspace::factory()->create(['user_id' => $user->id, 'name' => 'Workspace B']);
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace1->id]);
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace2->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->waitForText('Dashboard')
                ->click('@workspace-switcher')
                ->waitForText('Workspace A')
                ->waitForText('Workspace B')
                ->assertSee('Workspace A')
                ->assertSee('Workspace B');
        });
    }

    // T116: test_user_can_create_workspace_via_form
    public function test_user_can_create_workspace_via_form(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/workspace/create')
                ->waitForText('Criar Workspace')
                ->type('#name', 'Meu Novo Workspace')
                ->type('#description', 'Descrição do workspace')
                ->press('Criar Workspace')
                ->waitForText('Dashboard')
                ->assertPathIs('/dashboard');
        });

        $this->assertDatabaseHas('workspaces', [
            'name' => 'Meu Novo Workspace',
            'user_id' => $user->id,
        ]);
    }

    // T117: test_user_can_update_workspace_settings_via_ui
    public function test_user_can_update_workspace_settings_via_ui(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        $workspace = Workspace::factory()->create([
            'user_id' => $user->id,
            'name' => 'Workspace Original',
            'slug' => 'workspace-original',
        ]);
        Member::factory()->owner()->create(['user_id' => $user->id, 'workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($user, $workspace) {
            $browser->loginAs($user)
                ->visit("/workspace/{$workspace->id}/settings")
                ->waitForText('Workspace Original')
                ->clear('#name')
                ->type('#name', 'Workspace Atualizado')
                ->press('Salvar')
                ->waitForLocation("/workspace/{$workspace->id}/settings")
                ->assertSee('Workspace Atualizado');
        });

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Workspace Atualizado',
        ]);
    }
}
