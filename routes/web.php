<?php

use App\Modules\Admin\AdminController;
use App\Modules\Admin\EnsureAdmin;
use App\Modules\Planner\CalendarController;
use App\Modules\Planner\TaskController;
use App\Modules\Telegram\Auth\WebAppLoginController;
use App\Modules\Telegram\Webhook\WebhookController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => Inertia::render('Login'))->name('login');
    Route::post('/auth/telegram/webapp', WebAppLoginController::class)
        ->middleware('throttle:20,1')
        ->name('auth.telegram.webapp');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [TaskController::class, 'today'])->name('today');
    Route::get('/calendar', CalendarController::class)->name('calendar');

    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::post('/tasks/carry-over', [TaskController::class, 'carryOver'])->name('tasks.carry-over');
    Route::patch('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::post('/tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::post('/tasks/{task}/move-tomorrow', [TaskController::class, 'moveToTomorrow'])->name('tasks.move-tomorrow');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
});

Route::middleware(['auth', EnsureAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/bot', [AdminController::class, 'bot'])->name('bot');
    Route::post('/bot/webhook', [AdminController::class, 'setWebhook'])->name('bot.webhook');
    Route::post('/bot/profile', [AdminController::class, 'saveProfile'])->name('bot.profile');
    Route::post('/bot/commands', [AdminController::class, 'saveCommands'])->name('bot.commands');
    Route::post('/bot/texts', [AdminController::class, 'saveTexts'])->name('bot.texts');
});

Route::post('/telegram/webhook', WebhookController::class)
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->name('telegram.webhook');
