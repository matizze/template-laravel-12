<?php

namespace Modules\Workspace\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Http\Requests\CreateWorkspaceRequest;
use Modules\Workspace\Http\Requests\TransferOwnershipRequest;
use Modules\Workspace\Models\Member;
use Modules\Workspace\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class WorkspaceController extends Controller
{
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
