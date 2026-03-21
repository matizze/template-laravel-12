<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Http\Requests\InviteMemberRequest;
use App\Models\Invitation;
use App\Models\Member;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceInviteNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class MemberController extends Controller
{
    use AuthorizesRequests;

    public function index(Workspace $workspace): View
    {
        $this->authorize('manageMembers', $workspace);

        $members = $workspace->memberships()
            ->with('user')
            ->get();

        $pendingInvitations = $workspace->invitations()
            ->whereNull('accepted_at')
            ->get();

        return view('dashboard.members.index', [
            'workspace' => $workspace,
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }

    public function invite(InviteMemberRequest $request, Workspace $workspace): RedirectResponse
    {
        $invitation = Invitation::create([
            'workspace_id' => $workspace->id,
            'email' => $request->validated('email'),
            'role' => $request->validated('role'),
            'user_id' => $request->user()->id,
        ]);

        $invitee = User::where('email', $request->validated('email'))->first();

        if ($invitee) {
            $invitee->notify(new WorkspaceInviteNotification($invitation));
        }

        return redirect()
            ->route('workspace.members.index', $workspace)
            ->with('success', 'Convite enviado com sucesso!');
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)
            ->whereNull('accepted_at')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Este convite expirou.');
        }

        $user = $request->user();

        if (! $user) {
            session(['invitation_token' => $token]);

            return redirect()->route('login');
        }

        Member::create([
            'user_id' => $user->id,
            'workspace_id' => $invitation->workspace_id,
            'role' => $invitation->role,
        ]);

        $invitation->update(['accepted_at' => now()]);

        Workspace::setCurrent($invitation->workspace_id);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Convite aceito com sucesso!');
    }

    public function updateRole(Request $request, Workspace $workspace, Member $member): RedirectResponse
    {
        $this->authorize('manageMembers', $workspace);

        $request->validate([
            'role' => ['required', new Enum(WorkspaceRole::class)],
        ]);

        $member->update([
            'role' => $request->input('role'),
        ]);

        return redirect()
            ->route('workspace.members.index', $workspace)
            ->with('success', 'Função do membro atualizada com sucesso!');
    }

    public function remove(Workspace $workspace, Member $member): RedirectResponse
    {
        $this->authorize('manageMembers', $workspace);

        $member->delete();

        return redirect()
            ->route('workspace.members.index', $workspace)
            ->with('success', 'Membro removido com sucesso!');
    }

    public function leave(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();

        $member = Member::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($member->role === WorkspaceRole::Owner) {
            return redirect()
                ->back()
                ->with('error', 'O proprietário não pode sair do workspace. Transfira a propriedade antes de sair.');
        }

        $member->delete();

        Workspace::forgetCurrent();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Você saiu do workspace com sucesso.');
    }
}
