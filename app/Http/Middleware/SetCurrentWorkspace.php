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
        Workspace::forgetCurrent();

        if ($request->user()) {
            $workspaceId = session('current_workspace_id');

            if ($workspaceId) {
                $isMember = $request->user()
                    ->workspaces()
                    ->where('workspaces.id', $workspaceId)
                    ->exists();

                if ($isMember) {
                    Workspace::setCurrent($workspaceId);
                } else {
                    session()->forget('current_workspace_id');
                    $this->setDefaultWorkspace($request);
                }
            } else {
                $this->setDefaultWorkspace($request);
            }
        }

        return $next($request);
    }

    private function setDefaultWorkspace(Request $request): void
    {
        /** @var \App\Models\Workspace|null $firstWorkspace */
        $firstWorkspace = $request->user()->workspaces()->first();

        if ($firstWorkspace) {
            Workspace::setCurrent($firstWorkspace->id);
        }
    }
}
