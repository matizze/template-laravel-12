<?php

namespace Modules\Workspace\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Workspace\Http\Requests\UpdateWorkspaceSettingsRequest;
use Modules\Workspace\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceSettingsController extends Controller
{
    public function show(Request $request, Workspace $workspace): View
    {
        $this->authorize('update', $workspace);

        $tab = $request->query('tab', 'geral');

        $members = $workspace->memberships()->with('user')->get();
        $pendingInvitations = $workspace->invitations()->pending()->get();

        return view('workspace::dashboard.workspace-settings.index', [
            'workspace' => $workspace,
            'active' => $tab,
            'members' => $members,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }

    public function update(UpdateWorkspaceSettingsRequest $request, Workspace $workspace): RedirectResponse
    {
        $workspace->update($request->validated());

        return redirect()->route('workspace.settings.show', $workspace)
            ->with('success', 'Workspace atualizado com sucesso!');
    }

    public function destroy(Workspace $workspace): RedirectResponse
    {
        $this->authorize('delete', $workspace);

        Workspace::forgetCurrent();

        $workspace->delete();

        return redirect()->route('dashboard')
            ->with('success', 'Workspace excluído com sucesso!');
    }
}
