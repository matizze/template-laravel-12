<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\OnboardingController;
use Modules\Tenant\Http\Controllers\TenantController;
use Modules\Tenant\Http\Controllers\TenantSettingsController;
use Modules\Tenant\Http\Controllers\TenantUserController;
use Modules\Tenant\Http\Controllers\UserCreationController;

Route::middleware('web')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding');
        Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

        Route::middleware('tenant')->group(function () {
            Route::view('/dashboard', 'core::dashboard')->name('dashboard');

            Route::get('/tenant/{tenant}/users', [TenantUserController::class, 'index'])->name('tenant.users.index');
            Route::post('/tenant/{tenant}/leave', [TenantUserController::class, 'leave'])->name('tenant.users.leave');
            Route::patch('/tenant/{tenant}/users/{user}', [TenantUserController::class, 'updateRole'])->name('tenant.users.updateRole');
            Route::delete('/tenant/{tenant}/users/{user}', [TenantUserController::class, 'remove'])->name('tenant.users.remove');

            Route::get('/tenant/{tenant}/settings', [TenantSettingsController::class, 'show'])->name('tenant.settings.show');
            Route::patch('/tenant/{tenant}/settings', [TenantSettingsController::class, 'update'])->name('tenant.settings.update');
            Route::delete('/tenant/{tenant}', [TenantSettingsController::class, 'destroy'])->name('tenant.destroy');

            Route::post('/tenant/{tenant}/transfer', [TenantController::class, 'transferOwnership'])->name('tenant.transferOwnership');

            Route::get('/users/create', [UserCreationController::class, 'create'])->name('tenant.users.create');
            Route::post('/users/create', [UserCreationController::class, 'store'])
                ->middleware('throttle:5,1')
                ->name('tenant.users.create.store');
        });

        Route::post('/tenant', [TenantController::class, 'store'])->name('tenant.store');
        Route::post('/tenant/switch/{tenant}', [TenantController::class, 'switch'])->name('tenant.switch');
        Route::post('/tenant/{tenantId}/restore', [TenantSettingsController::class, 'restore'])
            ->name('tenant.restore')
            ->whereNumber('tenantId');
    });
});
