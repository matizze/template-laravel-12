<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Http\Requests\InviteMemberRequest;
use App\Mail\WorkspaceInviteMail;
use App\Models\Invitation;
use App\Models\Member;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceInviteNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Workspace $workspace): View
    {
        Gate::authorize('view', $workspace);

        $members = $workspace->members()
            ->with('user')
            ->paginate(15);

        $invitations = $workspace->invitations()
            ->whereNull('accepted_at')
            ->get();

        return view('dashboard.members.index', [
            'workspace' => $workspace,
            'members' => $members,
            'invitations' => $invitations,
        ]);
    }

    public function invite(InviteMemberRequest $request, Workspace $workspace): RedirectResponse
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if ($user && $workspace->hasUser($user)) {
            return back()->with('error', 'Este usuário já é membro do workspace.');
        }

        $invitation = $workspace->invitations()->create([
            'email' => $data['email'],
            'role' => $data['role'],
            'token' => Str::random(32),
            'user_id' => $user?->id,
        ]);

        if ($user) {
            $user->notify(new WorkspaceInviteNotification($invitation));
        } else {
            Mail::queue(new WorkspaceInviteMail($invitation));
        }

        return back()->with('success', 'Convite enviado com sucesso!');
    }

    public function accept(string $token): RedirectResponse
    {
        $invitation = Invitation::with('workspace')->where('token', $token)->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect()->route('dashboard')->with('warning', 'Este convite já foi aceito.');
        }

        if (! auth()->check()) {
            return redirect('/auth/login?next='.route('invitations.accept', $token));
        }

        $user = auth()->user();

        if (! $invitation->user_id && $invitation->email !== $user->email) {
            return back()->with('error', 'O email do convite não corresponde ao seu email.');
        }

        $invitation->accept($user);

        return redirect()->route('dashboard')->with('success', "Você agora é membro do workspace {$invitation->workspace->name}!");
    }

    public function updateRole(Request $request, Member $member): RedirectResponse
    {
        Gate::authorize('updateMemberRole', $member->workspace);

        $validated = $request->validate([
            'role' => ['required', Rule::enum(WorkspaceRole::class)],
        ]);

        $member->update(['role' => $validated['role']]);

        return back()->with('success', 'Role do membro atualizado com sucesso!');
    }

    public function remove(Member $member): RedirectResponse
    {
        Gate::authorize('removeMembers', $member->workspace);

        if ($member->isOwner()) {
            return back()->with('error', 'Não é possível remover o owner do workspace.');
        }

        $member->delete();

        return back()->with('success', 'Membro removido com sucesso!');
    }
}
