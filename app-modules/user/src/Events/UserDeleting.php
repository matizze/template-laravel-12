<?php

declare(strict_types=1);

namespace Modules\User\Events;

use Modules\User\Models\User;

/**
 * Fired inside the same DB transaction as a user account deletion, before the
 * row is removed. Listeners can rely on the user still being persisted and
 * having full relationships loaded; they should perform their cleanup as part
 * of the same atomic unit (synchronous listeners only).
 */
final class UserDeleting
{
    public function __construct(public readonly User $user) {}
}
