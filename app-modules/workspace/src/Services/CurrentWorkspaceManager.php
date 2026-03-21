<?php

namespace Modules\Workspace\Services;

use Modules\Workspace\Models\Workspace;

/**
 * Manages the current workspace for the request lifecycle.
 *
 * Registered as scoped in the container for automatic reset between Octane requests.
 */
class CurrentWorkspaceManager
{
    private ?Workspace $workspace = null;

    public function get(): ?Workspace
    {
        return $this->workspace ??= $this->resolveFromSession();
    }

    public function set(Workspace $workspace): void
    {
        $this->workspace = $workspace;
        session(['current_workspace_id' => $workspace->id]);
    }

    public function setById(int $workspaceId): void
    {
        $workspace = Workspace::find($workspaceId);

        if ($workspace) {
            $this->set($workspace);
        }
    }

    public function forget(): void
    {
        $this->workspace = null;
        session()->forget('current_workspace_id');
    }

    private function resolveFromSession(): ?Workspace
    {
        $workspaceId = session('current_workspace_id');

        return $workspaceId ? Workspace::find($workspaceId) : null;
    }
}
