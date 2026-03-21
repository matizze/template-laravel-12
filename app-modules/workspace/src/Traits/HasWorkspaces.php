<?php

namespace Modules\Workspace\Traits;

use Modules\Workspace\Enums\WorkspaceRole;
use Modules\Workspace\Models\Member;
use Modules\Workspace\Models\Workspace;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasWorkspaces
{
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'members')
            ->using(Member::class)
            ->withPivot('id', 'role')
            ->withCasts(['role' => WorkspaceRole::class])
            ->withTimestamps();
    }

    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    /** @var array<int, WorkspaceRole|null> */
    private array $roleCache = [];

    public function roleIn(Workspace $workspace): ?WorkspaceRole
    {
        if (array_key_exists($workspace->id, $this->roleCache)) {
            return $this->roleCache[$workspace->id];
        }

        /** @var Member|null $member */
        $member = $workspace->memberships()
            ->where('user_id', $this->id)
            ->first();

        /** @var WorkspaceRole|null $role */
        $role = $member?->role;

        return $this->roleCache[$workspace->id] = $role;
    }

    public function isMemberOf(Workspace $workspace): bool
    {
        return $this->roleIn($workspace) !== null;
    }
}
