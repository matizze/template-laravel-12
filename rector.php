<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Rector\ClassMethod\MakeModelAttributesAndScopesProtectedRector;
use RectorLaravel\Rector\FuncCall\AppToResolveRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/app-modules',
        __DIR__.'/database/seeders',
        __DIR__.'/app-modules/user/database/factories',
        __DIR__.'/app-modules/permission/database/factories',
        __DIR__.'/app-modules/tenant/database/factories',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/app-modules/*/database/migrations',
        __DIR__.'/database/migrations',
        __DIR__.'/_ide_helper_models.php',
        __DIR__.'/bootstrap/cache',
        AppToResolveRector::class,
        MakeModelAttributesAndScopesProtectedRector::class,
        FlipTypeControlToUseExclusiveTypeRector::class,
    ])
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        LaravelSetList::LARAVEL_CODE_QUALITY,
    ])
    ->withImportNames(removeUnusedImports: true);
