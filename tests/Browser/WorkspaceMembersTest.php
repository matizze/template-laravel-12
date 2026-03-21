<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\Concerns\CreatesWorkspace;
use Tests\DuskTestCase;

class WorkspaceMembersTest extends DuskTestCase
{
    use CreatesWorkspace;
    use DatabaseMigrations;

    private function openInviteModalViaEvent(Browser $browser): Browser
    {
        $browser->script("window.dispatchEvent(new CustomEvent('open-invite-modal'))");

        return $browser->waitFor('@invite-modal');
    }

    public function test_owner_can_see_invite_button(): void
    {
        [$user, $workspace] = $this->createOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($user, $workspace) {
            $browser->loginAs($user)
                ->visit(route('members.index', $workspace))
                ->assertVisible('@btn-invite-member')
                ->assertSee('Convidar membro');
        });
    }

    public function test_member_cannot_see_invite_button(): void
    {
        [, $member, $workspace] = $this->createOwnerAndMemberWithWorkspace();

        $this->browse(function (Browser $browser) use ($member, $workspace) {
            $browser->loginAs($member)
                ->visit(route('members.index', $workspace))
                ->assertMissing('@btn-invite-member');
        });
    }

    public function test_can_invite_member(): void
    {
        [$user, $workspace] = $this->createOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($user, $workspace) {
            $browser->loginAs($user)
                ->visit(route('members.index', $workspace));

            $this->openInviteModalViaEvent($browser)
                ->type('@input-invite-email', 'newmember@example.com')
                ->select('@select-invite-role', 'member')
                ->click('@btn-submit-invite')
                ->waitForText('Convite enviado com sucesso');
        });
    }

    public function test_invited_member_appears_in_pending_list(): void
    {
        [$user, $workspace] = $this->createOwnerWithWorkspace();

        $this->browse(function (Browser $browser) use ($user, $workspace) {
            $browser->loginAs($user)
                ->visit(route('members.index', $workspace));

            $this->openInviteModalViaEvent($browser)
                ->type('@input-invite-email', 'pending@example.com')
                ->select('@select-invite-role', 'admin')
                ->click('@btn-submit-invite')
                ->waitForText('Convite enviado com sucesso')
                ->waitFor('@pending-invitations-list')
                ->assertSee('pending@example.com')
                ->assertSee('Pendente');
        });
    }
}
