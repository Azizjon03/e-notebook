<?php

use App\Modules\Telegram\Auth\WebAppLoginController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => Inertia::render('Login'))->name('login');
    Route::post('/auth/telegram/webapp', WebAppLoginController::class)
        ->middleware('throttle:20,1')
        ->name('auth.telegram.webapp');
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => Inertia::render('Today', [
        'date' => now(auth()->user()->timezone)->toDateString(),
    ]))->name('today');
});
