<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Http\Requests\CreateWorkspaceRequest;
use App\Http\Requests\DeleteWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function create(): View
    {
        return view('dashboard.workspaces.create');
    }

    public function store(CreateWorkspaceRequest $request): RedirectResponse
    {
        $workspace = $request->user()->ownedWorkspaces()->create(
            $request->validated()
        );

        $request->user()->members()->create([
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Owner,
        ]);

        $request->session()->put('current_workspace_id', $workspace->id);
        Workspace::setCurrent($workspace);

        return redirect()->route('dashboard')->with('success', 'Workspace criado com sucesso!');
    }

    public function switch(Request $request, Workspace $workspace): RedirectResponse
    {
        Gate::authorize('view', $workspace);

        $request->session()->put('current_workspace_id', $workspace->id);
        Workspace::setCurrent($workspace);

        return redirect()->route('dashboard')->with('success', "Workspace alterado para {$workspace->name}");
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
        $workspace->update($request->validated());

        return back()->with('success', 'Workspace atualizado com sucesso!');
    }

    public function destroy(DeleteWorkspaceRequest $request, Workspace $workspace): RedirectResponse
    {
        $workspace->delete();

        return redirect()->route('dashboard')->with('success', 'Workspace deletado com sucesso!');
    }
}
