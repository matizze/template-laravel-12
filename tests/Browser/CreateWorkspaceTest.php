<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Concerns\CreatesWorkspace;
use Tests\DuskTestCase;

class CreateWorkspaceTest extends DuskTestCase
{
    use CreatesWorkspace;
    use DatabaseMigrations;

    private function openCreateModal(Browser $browser): Browser
    {
        return $browser->click('@workspace-switcher-toggle')
            ->waitFor('@new-workspace-btn')
            ->click('@new-workspace-btn')
            ->waitFor('@create-workspace-modal');
    }

    public function test_can_create_workspace_with_valid_data(): void
    {
        [$user] = $this->createOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($user) {
            $this->openCreateModal(
                $browser->loginAs($user)->visit('/dashboard')
            )
                ->type('@input-workspace-name', 'New Project')
                ->type('@input-workspace-slug', 'new-project')
                ->click('@btn-create-workspace')
                ->waitForText('Workspace criado com sucesso');
        });
    }

    public function test_new_workspace_appears_in_switcher_after_creation(): void
    {
        [$user] = $this->createOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($user) {
            $this->openCreateModal(
                $browser->loginAs($user)->visit('/dashboard')
            )
                ->type('@input-workspace-name', 'Brand New')
                ->type('@input-workspace-slug', 'brand-new')
                ->click('@btn-create-workspace')
                ->waitForText('Workspace criado com sucesso')
                ->click('@workspace-switcher-toggle')
                ->assertSee('Brand New');
        });
    }

    public function test_can_close_modal_with_escape_key(): void
    {
        [$user] = $this->createOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($user) {
            $this->openCreateModal(
                $browser->loginAs($user)->visit('/dashboard')
            )
                ->keys('', '{escape}')
                ->waitUntilMissing('@create-workspace-modal');
        });
    }

    public function test_can_close_modal_with_cancel_button(): void
    {
        [$user] = $this->createOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($user) {
            $this->openCreateModal(
                $browser->loginAs($user)->visit('/dashboard')
            )
                ->press('Cancelar')
                ->waitUntilMissing('@create-workspace-modal');
        });
    }
}
