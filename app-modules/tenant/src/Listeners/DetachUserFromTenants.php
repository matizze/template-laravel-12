<?php

declare(strict_types=1);

namespace Modules\Tenant\Listeners;

use Modules\User\Events\UserDeleting;

/**
 * Detaches a deleting user from every tenant they belong to. Runs synchronously
 * inside the same transaction as the user deletion (UserDeleting is dispatched
 * before the row is removed) so the cleanup is atomic.
 */
final class DetachUserFromTenants
{
    public function handle(UserDeleting $event): void
    {
        $event->user->tenants()->detach();
    }
}
