<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\ForgotPasswordController;
use Modules\Auth\Http\Controllers\LoginController;
use Modules\Auth\Http\Controllers\RegisterController;
use Modules\Auth\Http\Controllers\ResetPasswordController;
use Modules\Tenant\Http\Controllers\TenantController;
use Modules\Tenant\Http\Controllers\TenantSettingsController;
use Modules\Tenant\Http\Controllers\TenantUserController;
use Modules\User\Http\Controllers\SettingsController;
use Modules\User\Http\Controllers\UserController;
use Modules\User\Http\Controllers\UserCreationController;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('/auth/login', [LoginController::class, 'store'])->name('login');
        Route::post('/auth/register', [RegisterController::class, 'store'])->name('register');
    });

    Route::middleware('throttle:3,1')->group(function () {
        Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
        Route::post('/auth/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::post('/auth/logout', [LoginController::class, 'destroy'])->name('logout');

        Route::get('/user/profile', [SettingsController::class, 'showProfile'])->name('user.profile.show');
        Route::patch('/user/profile', [SettingsController::class, 'updateProfile'])->name('user.profile.update');
        Route::patch('/user/password', [SettingsController::class, 'updatePassword'])->name('user.password.update');
        Route::delete('/user/account', [SettingsController::class, 'destroy'])
            ->middleware('throttle:10,1')
            ->name('user.account.destroy');

        Route::post('/users', [UserController::class, 'store'])
            ->middleware('can:users.create')
            ->name('users.store');

        Route::patch('/users/{user}', [UserController::class, 'update'])
            ->middleware('can:users.update')
            ->name('users.update');

        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->middleware(['can:users.delete', 'throttle:10,1'])
            ->name('users.destroy');

        Route::post('/users/create', [UserCreationController::class, 'store'])
            ->middleware('can:users.create')
            ->name('users.create.store');

        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('/tenants', [TenantController::class, 'store'])->name('tenant.store');

        Route::middleware('tenant')->group(function () {
            Route::get('/tenants/{tenant}', [TenantSettingsController::class, 'show'])->name('tenant.settings.show');
            Route::patch('/tenants/{tenant}', [TenantSettingsController::class, 'update'])->name('tenant.settings.update');
            Route::delete('/tenants/{tenant}', [TenantSettingsController::class, 'destroy'])
                ->middleware('throttle:10,1')
                ->name('tenant.destroy');

            Route::get('/tenants/{tenant}/users', [TenantUserController::class, 'index'])->name('tenant.users.index');
            Route::post('/tenants/{tenant}/users', [TenantUserController::class, 'store'])->name('tenant.users.store');
            Route::post('/tenants/{tenant}/leave', [TenantUserController::class, 'leave'])->name('tenant.users.leave');
            Route::delete('/tenants/{tenant}/users/{user}', [TenantUserController::class, 'remove'])
                ->middleware('throttle:10,1')
                ->name('tenant.users.remove')
                ->scopeBindings();
        });

        Route::post('/tenants/{tenantId}/restore', [TenantSettingsController::class, 'restore'])
            ->middleware('throttle:10,1')
            ->name('tenant.restore')
            ->whereNumber('tenantId');
    });
});
