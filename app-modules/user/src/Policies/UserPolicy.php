<?php

declare(strict_types=1);

namespace Modules\User\Policies;

use Modules\User\Models\User;

class UserPolicy
{
    public function update(User $actor, User $target): bool
    {
        return ! $actor->is($target);
    }

    public function delete(User $actor, User $target): bool
    {
        return ! $actor->is($target);
    }
}
