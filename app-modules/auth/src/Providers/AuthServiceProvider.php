<?php

namespace Modules\Auth\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Modules\Auth\Notifications\ResetPasswordUrlBuilder;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        ResetPassword::createUrlUsing(app(ResetPasswordUrlBuilder::class));
    }
}
