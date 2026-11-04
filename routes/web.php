<?php

use App\Modules\Admin\AdminController;
use App\Modules\Admin\EnsureAdmin;
use App\Modules\Core\SettingsController;
use App\Modules\Core\StatsController;
use App\Modules\Ilm\BookController;
use App\Modules\Planner\CalendarController;
use App\Modules\Planner\TaskController;
use App\Modules\Routine\HabitController;
use App\Modules\Sport\GymController;
use App\Modules\Study\StudyController;
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

    Route::get('/habits', [HabitController::class, 'index'])->name('habits');
    Route::post('/habits', [HabitController::class, 'store'])->name('habits.store');
    Route::patch('/habits/{habit}', [HabitController::class, 'update'])->name('habits.update');
    Route::delete('/habits/{habit}', [HabitController::class, 'destroy'])->name('habits.destroy');
    Route::post('/habits/{habit}/entry', [HabitController::class, 'entry'])->name('habits.entry');

    Route::get('/gym', [GymController::class, 'index'])->name('gym');
    Route::post('/gym/gender', [GymController::class, 'gender'])->name('gym.gender');
    Route::post('/gym/program', [GymController::class, 'program'])->name('gym.program');
    Route::post('/gym/set', [GymController::class, 'set'])->name('gym.set');
    Route::post('/gym/finish', [GymController::class, 'finish'])->name('gym.finish');

    Route::get('/stats', StatsController::class)->name('stats');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    Route::get('/books', [BookController::class, 'index'])->name('books');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::patch('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    Route::post('/books/{book}/move', [BookController::class, 'move'])->name('books.move');
    Route::post('/books/{book}/log', [BookController::class, 'log'])->name('books.log');

    Route::get('/study', [StudyController::class, 'index'])->name('study');
    Route::post('/study/start', [StudyController::class, 'start'])->name('study.start');
    Route::post('/study/{session}/stop', [StudyController::class, 'stop'])->name('study.stop');
    Route::delete('/study/{session}', [StudyController::class, 'destroy'])->name('study.destroy');

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
