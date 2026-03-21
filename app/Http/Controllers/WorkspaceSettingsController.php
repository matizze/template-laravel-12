<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWorkspaceSettingsRequest;
use App\Models\Workspace;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceSettingsController extends Controller
{
    use AuthorizesRequests;

    public function show(Request $request, Workspace $workspace): View
    {
        $this->authorize('update', $workspace);

        $tab = $request->query('tab', 'geral');

        return view('dashboard.workspace-settings.index', [
            'workspace' => $workspace,
            'active' => $tab,
        ]);
    }

    public function update(UpdateWorkspaceSettingsRequest $request, Workspace $workspace): RedirectResponse
    {
        $this->authorize('update', $workspace);

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
