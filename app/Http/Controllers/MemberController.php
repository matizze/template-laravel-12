<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Http\Requests\InviteMemberRequest;
use App\Http\Requests\UpdateMemberRoleRequest;
use App\Models\Invitation;
use App\Models\Member;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceInviteNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Workspace $workspace): View
    {
        $this->authorize('manageMembers', $workspace);

        $members = $workspace->memberships()
            ->with('user')
            ->get();

        $pendingInvitations = $workspace->invitations()->whereNull('accepted_at')->get();

        return view('dashboard.members.index', [
            'workspace' => $workspace,
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }

    public function invite(InviteMemberRequest $request, Workspace $workspace): RedirectResponse
    {
        $email = $request->validated('email');

        $invitation = DB::transaction(function () use ($workspace, $email, $request): Invitation {
            Invitation::where('workspace_id', $workspace->id)
                ->where('email', $email)
                ->whereNotNull('accepted_at')
                ->delete();

            return Invitation::create([
                'workspace_id' => $workspace->id,
                'email' => $email,
                'role' => $request->validated('role'),
                'user_id' => $request->user()->id,
            ]);
        });

        $invitee = User::where('email', $email)->first();

        if ($invitee) {
            $invitee->notify(new WorkspaceInviteNotification($invitation));
        } else {
            Notification::route('mail', $request->validated('email'))
                ->notify(new WorkspaceInviteNotification($invitation));
        }

        return redirect()
            ->route('workspace.settings.show', $workspace)
            ->with('success', 'Convite enviado com sucesso!');
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)
            ->pending()
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Este convite expirou.');
        }

        $user = $request->user();

        if (! $user) {
            $inviteeExists = User::where('email', $invitation->email)->exists();
            $acceptPath = route('invitation.accept', $token, false);

            if ($inviteeExists) {
                return redirect(route('login').'?redirect='.urlencode($acceptPath));
            }

            return redirect(route('register').'?redirect='.urlencode($acceptPath));
        }

        if ($user->email !== $invitation->email) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Este convite foi enviado para outro endereço de e-mail.');
        }

        DB::transaction(function () use ($user, $invitation): void {
            Member::create([
                'user_id' => $user->id,
                'workspace_id' => $invitation->workspace_id,
                'role' => $invitation->role,
            ]);

            $invitation->update(['accepted_at' => now()]);
        });

        $request->session()->forget('invitation_token');

        Workspace::setCurrentModel($invitation->workspace);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Convite aceito com sucesso!');
    }

    public function updateRole(UpdateMemberRoleRequest $request, Workspace $workspace, User $user): RedirectResponse
    {
        $workspace->memberships()->where('user_id', $user->id)->firstOrFail();

        $newRole = WorkspaceRole::from($request->validated('role'));

        if ($newRole === WorkspaceRole::Owner) {
            return redirect()
                ->route('workspace.members.index', $workspace)
                ->with('error', 'Não é possível promover um membro a proprietário. Use a transferência de propriedade.');
        }

        $workspace->members()->updateExistingPivot($user->id, [
            'role' => $newRole,
        ]);

        return redirect()
            ->route('workspace.members.index', $workspace)
            ->with('success', 'Função do membro atualizada com sucesso!');
    }

    public function remove(Workspace $workspace, User $user): RedirectResponse
    {
        $this->authorize('manageMembers', $workspace);

        /** @var Member $member */
        $member = $workspace->memberships()->where('user_id', $user->id)->firstOrFail();

        if ($member->role === WorkspaceRole::Owner) {
            return redirect()
                ->route('workspace.settings.show', $workspace)
                ->with('error', 'Não é possível remover o proprietário do workspace.');
        }

        $workspace->members()->detach($user->id);

        return redirect()
            ->route('workspace.settings.show', $workspace)
            ->with('success', 'Membro removido com sucesso!');
    }

    public function leave(Request $request, Workspace $workspace): RedirectResponse
    {
        $user = $request->user();

        /** @var Member $member */
        $member = $workspace->memberships()->where('user_id', $user->id)->firstOrFail();

        if ($member->role === WorkspaceRole::Owner) {
            return redirect()
                ->back()
                ->with('error', 'O proprietário não pode sair do workspace. Transfira a propriedade antes de sair.');
        }

        $workspace->members()->detach($user->id);

        Workspace::forgetCurrent();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Você saiu do workspace com sucesso.');
    }
}
