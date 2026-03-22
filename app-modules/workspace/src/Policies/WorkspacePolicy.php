<?php

namespace Modules\Workspace\Policies;

use Modules\User\Models\User;
use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Models\Member;
use Modules\Workspace\Models\Workspace;

class WorkspacePolicy
{
    /** @var array<string, WorkspaceRole|null> */
    private array $roleCache = [];

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
        $key = "{$user->id}:{$workspace->id}";

        if (! array_key_exists($key, $this->roleCache)) {
            /** @var Member|null $member */
            $member = $workspace->memberships()
                ->where('user_id', $user->id)
                ->first();

            $this->roleCache[$key] = $member?->role;
        }

        return $this->roleCache[$key];
    }
}
