<?php

declare(strict_types=1);

namespace Modules\Core\Providers;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (App::environment('local')) {
            Event::listen(
                MigrationsEnded::class,
                function () {
                    Artisan::call('ide-helper:generate');
                    Artisan::call('ide-helper:models', ['--nowrite' => true, '--reset' => true]);
                }
            );
        }
    }
}
