<?php

namespace App\Http\Middleware;

use App\Models\Workspace;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $workspaceId = session('current_workspace_id');

            /** @var Workspace|null $workspace */
            $workspace = $workspaceId
                ? $request->user()->workspaces()->where('workspaces.id', $workspaceId)->first()
                : $request->user()->workspaces()->first();

            if ($workspace) {
                Workspace::setCurrentModel($workspace);
            } else {
                session()->forget('current_workspace_id');
            }
        }

        return $next($request);
    }
}
