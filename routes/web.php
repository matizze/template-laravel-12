<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceSettingsController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'guest'], function () {
    Route::redirect('/', '/auth/login');
});

Route::group(['middleware' => 'auth'], function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Workspace routes
    Route::get('/workspace/create', [WorkspaceController::class, 'create'])->name('workspace.create');
    Route::post('/workspace', [WorkspaceController::class, 'store'])->name('workspace.store');
    Route::post('/workspace/switch/{workspace}', [WorkspaceController::class, 'switch'])->name('workspace.switch');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::patch('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::delete('/settings/account', [SettingsController::class, 'destroy'])->name('settings.account.destroy');

    Route::middleware('can:manage-users')->group(function () {
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Member management routes (requires workspace context)
    Route::middleware('workspace')->group(function () {
        Route::get('/workspace/{workspace}/members', [MemberController::class, 'index'])->name('workspace.members.index');
        Route::post('/workspace/{workspace}/invite', [MemberController::class, 'invite'])->middleware('throttle:10,1')->name('workspace.members.invite');
        Route::post('/workspace/{workspace}/leave', [MemberController::class, 'leave'])->name('workspace.members.leave');
        Route::patch('/workspace/{workspace}/members/{member}', [MemberController::class, 'updateRole'])->name('workspace.members.updateRole');
        Route::delete('/workspace/{workspace}/members/{member}', [MemberController::class, 'remove'])->name('workspace.members.remove');
    });

    Route::post('/workspace/{workspace}/transfer', [WorkspaceController::class, 'transferOwnership'])->name('workspace.transferOwnership');

    Route::get('/invitation/{token}', [MemberController::class, 'accept'])->name('invitation.accept');

    // Workspace settings routes
    Route::get('/workspace/{workspace}/settings', [WorkspaceSettingsController::class, 'show'])->name('workspace.settings.show');
    Route::patch('/workspace/{workspace}/settings', [WorkspaceSettingsController::class, 'update'])->name('workspace.settings.update');
    Route::delete('/workspace/{workspace}', [WorkspaceSettingsController::class, 'destroy'])->name('workspace.destroy');
});

require __DIR__.'/auth.php';
