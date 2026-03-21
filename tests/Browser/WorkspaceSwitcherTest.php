<?php

namespace Tests\Browser;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Concerns\CreatesWorkspace;
use Tests\DuskTestCase;

class WorkspaceSwitcherTest extends DuskTestCase
{
    use CreatesWorkspace;
    use DatabaseMigrations;

    public function test_workspace_switcher_is_visible_on_dashboard(): void
    {
        [$user] = $this->createOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertVisible('@workspace-switcher-toggle');
        });
    }

    public function test_can_open_and_close_workspace_switcher(): void
    {
        [$user, $workspace] = $this->createOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($user, $workspace) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->click('@workspace-switcher-toggle')
                ->waitFor('@workspace-switcher-dropdown')
                ->assertSee($workspace->name)
                ->assertSee('Novo workspace')
                ->keys('', '{escape}');
        });
    }

    public function test_can_switch_workspace(): void
    {
        [$user, $ws1] = $this->createOwnerWithWorkspace('Alpha');
        $ws2 = Workspace::factory()->for($user, 'owner')->create(['name' => 'Beta']);
        $user->workspaces()->attach($ws2, ['role' => WorkspaceRole::Member]);

        $this->browse(function (Browser $browser) use ($user, $ws1, $ws2) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->assertSee('Alpha')
                ->click('@workspace-switcher-toggle')
                ->waitFor('@workspace-item-'.$ws2->id)
                ->click('@workspace-item-'.$ws2->id)
                ->waitForText('Beta')
                ->assertSee('Beta')
                ->click('@workspace-switcher-toggle')
                ->waitFor('@workspace-item-'.$ws1->id)
                ->click('@workspace-item-'.$ws1->id)
                ->waitForText('Alpha')
                ->assertSee('Alpha');
        });
    }

    public function test_can_open_create_workspace_modal(): void
    {
        [$user] = $this->createOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/dashboard')
                ->click('@workspace-switcher-toggle')
                ->waitFor('@new-workspace-btn')
                ->click('@new-workspace-btn')
                ->waitFor('@create-workspace-modal');
        });
    }
}
