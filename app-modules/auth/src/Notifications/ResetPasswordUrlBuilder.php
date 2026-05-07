<?php

namespace Modules\Auth\Notifications;

use Modules\User\Models\User;

final class ResetPasswordUrlBuilder
{
    public function __invoke(User $user, string $token): string
    {
        $base = rtrim((string) config('app.frontend_url'), '/');
        $query = http_build_query([
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]);

        return "{$base}/reset-password?{$query}";
    }
}
