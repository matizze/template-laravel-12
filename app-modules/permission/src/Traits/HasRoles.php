<?php

namespace Modules\Permission\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Permission\Models\Role;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withTimestamps();
    }
}
