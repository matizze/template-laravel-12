<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Http\Requests\CreateWorkspaceRequest;
use App\Http\Requests\TransferOwnershipRequest;
use App\Models\Member;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function create(): View
    {
        return view('dashboard.workspaces.create');
    }

    public function store(CreateWorkspaceRequest $request): RedirectResponse
    {
        $workspace = DB::transaction(function () use ($request): Workspace {
            $workspace = Workspace::create([
                'name' => $request->validated('name'),
                'slug' => $request->validated('slug'),
                'description' => $request->validated('description'),
                'user_id' => $request->user()->id,
            ]);

            Member::create([
                'user_id' => $request->user()->id,
                'workspace_id' => $workspace->id,
                'role' => WorkspaceRole::Owner,
            ]);

            return $workspace;
        });

        Workspace::setCurrentModel($workspace);

        return redirect()->route('dashboard')
            ->with('success', 'Workspace criado com sucesso!');
    }

    public function transferOwnership(TransferOwnershipRequest $request, Workspace $workspace): RedirectResponse
    {
        $newOwnerId = $request->validated('user_id');

        DB::transaction(function () use ($workspace, $request, $newOwnerId): void {
            $workspace->members()->updateExistingPivot($request->user()->id, [
                'role' => WorkspaceRole::Admin,
            ]);

            $workspace->members()->updateExistingPivot($newOwnerId, [
                'role' => WorkspaceRole::Owner,
            ]);

            $workspace->update(['user_id' => $newOwnerId]);
        });

        return redirect()
            ->route('dashboard')
            ->with('success', 'Propriedade do workspace transferida com sucesso!');
    }

    public function switch(Workspace $workspace): RedirectResponse
    {
        $this->authorize('view', $workspace);

        Workspace::setCurrentModel($workspace);

        return redirect()->back()
            ->with('success', 'Workspace alterado com sucesso!');
    }
}
