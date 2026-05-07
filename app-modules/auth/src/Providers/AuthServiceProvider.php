<?php

namespace Modules\Auth\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Modules\User\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            $base = rtrim((string) config('app.frontend_url'), '/');
            $query = http_build_query([
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);

            return "{$base}/reset-password?{$query}";
        });
    }
}
