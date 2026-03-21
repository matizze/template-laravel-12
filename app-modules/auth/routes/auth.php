<?php

use Modules\Auth\Http\Controllers\ForgotPasswordController;
use Modules\Auth\Http\Controllers\LoginController;
use Modules\Auth\Http\Controllers\RegisterController;
use Modules\Auth\Http\Controllers\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::group(['middleware' => 'guest'], function () {
        Route::get('/auth/login', [LoginController::class, 'index']);
        Route::post('/auth/login', [LoginController::class, 'store'])->name('login')->middleware('throttle:5,1');

        Route::get('/auth/register', [RegisterController::class, 'index']);
        Route::post('/auth/register', [RegisterController::class, 'store'])->name('register')->middleware('throttle:5,1');

        Route::get('/auth/forgot-password', [ForgotPasswordController::class, 'index'])->name('password.request');
        Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email')->middleware('throttle:3,1');

        Route::get('/auth/reset-password/{token}', [ResetPasswordController::class, 'index'])->name('password.reset');
        Route::post('/auth/reset-password', [ResetPasswordController::class, 'store'])->name('password.update')->middleware('throttle:3,1');
    });

    Route::group(['middleware' => 'auth'], function () {
        Route::post('/auth/logout', [LoginController::class, 'destroy'])->name('logout');
    });
});
