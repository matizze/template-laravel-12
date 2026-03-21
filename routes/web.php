<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'guest'], function () {
    Route::redirect('/', '/auth/login');
});

Route::group(['middleware' => ['auth', \App\Http\Middleware\SetCurrentWorkspace::class]], function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::patch('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::delete('/settings/account', [SettingsController::class, 'destroy'])->name('settings.account.destroy');

    Route::middleware('can:manage-users')->group(function () {
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Workspace routes
    Route::get('/workspace/create', [WorkspaceController::class, 'create'])->name('workspace.create');
    Route::post('/workspace', [WorkspaceController::class, 'store'])->name('workspace.store');
    Route::post('/workspace/{workspace}/switch', [WorkspaceController::class, 'switch'])->name('workspace.switch');
    Route::patch('/workspace/{workspace}', [WorkspaceController::class, 'update'])->name('workspace.update');
    Route::delete('/workspace/{workspace}', [WorkspaceController::class, 'destroy'])->name('workspace.destroy');

    // Member routes
    Route::get('/workspace/{workspace}/members', [MemberController::class, 'index'])->name('members.index');
    Route::post('/workspace/{workspace}/invite', [MemberController::class, 'invite'])->name('members.invite');
    Route::patch('/members/{member}/role', [MemberController::class, 'updateRole'])->name('members.updateRole');
    Route::delete('/members/{member}', [MemberController::class, 'remove'])->name('members.remove');
});

// Invitation acceptance route (can be public for new users)
Route::get('/invitations/{token}/accept', [MemberController::class, 'accept'])->name('invitations.accept');

require __DIR__.'/auth.php';
