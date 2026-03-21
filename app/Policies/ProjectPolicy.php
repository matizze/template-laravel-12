<?php

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        $workspace = Workspace::current();

        if (! $workspace) {
            return false;
        }

        return $user->roleIn($workspace) !== null;
    }

    public function create(User $user): bool
    {
        $workspace = Workspace::current();

        if (! $workspace) {
            return false;
        }

        $role = $user->roleIn($workspace);

        return in_array($role, [WorkspaceRole::Owner, WorkspaceRole::Admin, WorkspaceRole::Member]);
    }

    public function update(User $user, Project $project): bool
    {
        $workspace = Workspace::current();

        if (! $workspace) {
            return false;
        }

        $role = $user->roleIn($workspace);

        return in_array($role, [WorkspaceRole::Owner, WorkspaceRole::Admin, WorkspaceRole::Member]);
    }

    public function delete(User $user, Project $project): bool
    {
        $workspace = Workspace::current();

        if (! $workspace) {
            return false;
        }

        return in_array($user->roleIn($workspace), [WorkspaceRole::Owner, WorkspaceRole::Admin]);
    }
}
