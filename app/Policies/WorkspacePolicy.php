<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $user->hasWorkspaceAccess($workspace);
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $this->isOwner($user, $workspace);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $this->isOwner($user, $workspace);
    }

    public function invite(User $user, Workspace $workspace): bool
    {
        return $this->canManageMembers($user, $workspace);
    }

    public function removeMembers(User $user, Workspace $workspace): bool
    {
        return $this->canManageMembers($user, $workspace);
    }

    public function updateMemberRole(User $user, Workspace $workspace): bool
    {
        return $this->canManageMembers($user, $workspace);
    }

    private function isOwner(User $user, Workspace $workspace): bool
    {
        return $user->roleIn($workspace)?->isOwner() ?? false;
    }

    private function canManageMembers(User $user, Workspace $workspace): bool
    {
        return $user->roleIn($workspace)?->canManageMembers() ?? false;
    }
}
