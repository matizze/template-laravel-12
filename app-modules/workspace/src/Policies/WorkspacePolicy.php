<?php

namespace Modules\Workspace\Policies;

use Modules\User\Models\User;
use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        return $this->roleIn($user, $workspace) !== null;
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $this->roleIn($user, $workspace) === WorkspaceRole::Owner;
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $this->roleIn($user, $workspace) === WorkspaceRole::Owner;
    }

    public function manageMembers(User $user, Workspace $workspace): bool
    {
        $role = $this->roleIn($user, $workspace);

        return in_array($role, [WorkspaceRole::Owner, WorkspaceRole::Admin]);
    }

    public function transferOwnership(User $user, Workspace $workspace): bool
    {
        return $this->roleIn($user, $workspace) === WorkspaceRole::Owner;
    }

    private function roleIn(User $user, Workspace $workspace): ?WorkspaceRole
    {
        /** @var \Modules\Workspace\Models\Member|null $member */
        $member = $workspace->memberships()
            ->where('user_id', $user->id)
            ->first();

        return $member?->role;
    }
}
