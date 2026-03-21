<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $project->workspace && $user->hasWorkspaceAccess($project->workspace);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $this->hasWorkspaceRole($user, $project, 'canViewAllContent');
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->hasWorkspaceRole($user, $project, 'canManageMembers');
    }

    private function hasWorkspaceRole(User $user, Project $project, string $ability): bool
    {
        if (! $project->workspace) {
            return false;
        }

        return $user->roleIn($project->workspace)?->$ability() ?? false;
    }
}
