<?php

namespace App\Http\Controllers;

use App\Enums\WorkspaceRole;
use App\Http\Requests\CreateWorkspaceRequest;
use App\Http\Requests\TransferOwnershipRequest;
use App\Models\Member;
use App\Models\Workspace;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    use AuthorizesRequests;

    public function create(): View
    {
        return view('dashboard.workspaces.create');
    }

    public function store(CreateWorkspaceRequest $request): RedirectResponse
    {
        $name = $request->validated('name');
        $slug = $request->validated('slug') ?: Str::slug($name);

        $workspace = Workspace::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $request->validated('description'),
            'user_id' => $request->user()->id,
        ]);

        Member::create([
            'user_id' => $request->user()->id,
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Owner,
        ]);

        Workspace::setCurrent($workspace->id);

        return redirect()->route('dashboard')
            ->with('success', 'Workspace criado com sucesso!');
    }

    public function transferOwnership(TransferOwnershipRequest $request, Workspace $workspace): RedirectResponse
    {
        $newOwnerId = $request->validated('user_id');

        // Demote old owner to admin
        Member::where('workspace_id', $workspace->id)
            ->where('user_id', $request->user()->id)
            ->update(['role' => WorkspaceRole::Admin]);

        // Promote new owner
        Member::where('workspace_id', $workspace->id)
            ->where('user_id', $newOwnerId)
            ->update(['role' => WorkspaceRole::Owner]);

        // Update workspace owner
        $workspace->update(['user_id' => $newOwnerId]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Propriedade do workspace transferida com sucesso!');
    }

    public function switch(Workspace $workspace): RedirectResponse
    {
        $isMember = auth()->user()
            ->workspaces()
            ->where('workspaces.id', $workspace->id)
            ->exists();

        if (! $isMember) {
            return redirect()->route('dashboard')
                ->with('error', 'Você não tem acesso a este workspace.');
        }

        Workspace::setCurrent($workspace->id);

        return redirect()->back()
            ->with('success', 'Workspace alterado com sucesso!');
    }
}
