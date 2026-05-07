<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->components->addSecurityScheme(
                    'bearer',
                    SecurityScheme::http('bearer')
                        ->setDescription('Token Sanctum retornado por POST /api/auth/login')
                );
            })
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo): void {
                if (\in_array('auth:sanctum', $routeInfo->route->gatherMiddleware(), true)) {
                    $operation->addSecurity(new SecurityRequirement(['bearer' => []]));
                }
            });

        if (App::environment('local')) {
            Event::listen(
                MigrationsEnded::class,
                function (): void {
                    Artisan::call('ide-helper:generate');
                    Artisan::call('ide-helper:models', ['--nowrite' => true, '--reset' => true]);
                }
            );
        }
    }
}
