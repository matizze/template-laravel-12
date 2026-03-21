<?php

namespace App\Http\Middleware;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $workspace = $this->resolveWorkspace($request, $user);

        $request->session()->put('current_workspace_id', $workspace->id);
        Workspace::setCurrent($workspace);

        return $next($request);
    }

    private function resolveWorkspace(Request $request, User $user): Workspace
    {
        $currentWorkspaceId = $request->session()->get('current_workspace_id');

        if ($currentWorkspaceId) {
            $workspace = $user->workspaces()
                ->where('workspaces.id', $currentWorkspaceId)
                ->first();

            if ($workspace) {
                return $workspace;
            }
        }

        $workspace = $user->workspaces()->first();

        if ($workspace) {
            return $workspace;
        }

        $workspace = Workspace::create([
            'user_id' => $user->id,
            'name' => $user->name."'s Workspace",
            'slug' => 'workspace-'.$user->id,
        ]);

        $user->members()->create([
            'workspace_id' => $workspace->id,
            'role' => WorkspaceRole::Owner,
        ]);

        return $workspace;
    }
}
