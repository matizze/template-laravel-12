<?php

use Illuminate\Support\Facades\Route;
use Modules\Workspace\Http\Controllers\MemberController;
use Modules\Workspace\Http\Controllers\OnboardingController;
use Modules\Workspace\Http\Controllers\WorkspaceController;
use Modules\Workspace\Http\Controllers\WorkspaceSettingsController;

Route::middleware('web')->group(function () {
    Route::middleware('auth')->group(function () {
        Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding');
        Route::post('/onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');

        Route::middleware('workspace')->group(function () {
            Route::view('/dashboard', 'core::dashboard')->name('dashboard');

            Route::get('/workspace/{workspace}/members', [MemberController::class, 'index'])->name('workspace.members.index');
            Route::post('/workspace/{workspace}/invite', [MemberController::class, 'invite'])->middleware('throttle:10,1')->name('workspace.members.invite');
            Route::post('/workspace/{workspace}/leave', [MemberController::class, 'leave'])->name('workspace.members.leave');
            Route::patch('/workspace/{workspace}/members/{user}', [MemberController::class, 'updateRole'])->name('workspace.members.updateRole');
            Route::delete('/workspace/{workspace}/members/{user}', [MemberController::class, 'remove'])->name('workspace.members.remove');

            Route::get('/workspace/{workspace}/settings', [WorkspaceSettingsController::class, 'show'])->name('workspace.settings.show');
            Route::patch('/workspace/{workspace}/settings', [WorkspaceSettingsController::class, 'update'])->name('workspace.settings.update');
            Route::delete('/workspace/{workspace}', [WorkspaceSettingsController::class, 'destroy'])->name('workspace.destroy');

            Route::post('/workspace/{workspace}/transfer', [WorkspaceController::class, 'transferOwnership'])->name('workspace.transferOwnership');
        });

        Route::post('/workspace', [WorkspaceController::class, 'store'])->name('workspace.store');
        Route::post('/workspace/switch/{workspace}', [WorkspaceController::class, 'switch'])->name('workspace.switch');
    });

    Route::get('/invitation/{token}', [MemberController::class, 'accept'])->name('invitation.accept');
});
