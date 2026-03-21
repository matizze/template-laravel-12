<?php

namespace Modules\Workspace\Policies;

use Modules\Workspace\Enums\WorkspaceRole;
use Modules\User\Models\User;
use Modules\Workspace\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $user->roleIn($workspace) !== null;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $user->roleIn($workspace) === WorkspaceRole::Owner;
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $user->roleIn($workspace) === WorkspaceRole::Owner;
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        $role = $user->roleIn($workspace);

        return in_array($role, [WorkspaceRole::Owner, WorkspaceRole::Admin]);
    }

    public function transferOwnership(User $user, Workspace $workspace): bool
    {
        return $user->roleIn($workspace) === WorkspaceRole::Owner;
    }
}
