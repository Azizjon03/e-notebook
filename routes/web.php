<?php

use App\Modules\Admin\AdminController;
use App\Modules\Admin\EnsureAdmin;
use App\Modules\Core\Auth\LoginSettingsController;
use App\Modules\Core\Auth\PasswordLoginController;
use App\Modules\Core\Auth\RegisterController;
use App\Modules\Core\Demo\DemoController;
use App\Modules\Core\Dev\DevLoginController;
use App\Modules\Core\I18n\LocaleController;
use App\Modules\Core\ProfileController;
use App\Modules\Core\SettingsController;
use App\Modules\Core\StatsController;
use App\Modules\Ilm\BookController;
use App\Modules\Ilm\BookReaderController;
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
    Route::get('/login', fn () => Inertia::render('Login', ['devLogin' => DevLoginController::enabled()]))->name('login');
    Route::post('/auth/telegram/webapp', WebAppLoginController::class)
        ->middleware('throttle:20,1')
        ->name('auth.telegram.webapp');
    Route::post('/login', [PasswordLoginController::class, 'login'])->middleware('throttle:10,1')->name('login.password');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/demo', [DemoController::class, 'enter'])->middleware('throttle:20,1')->name('demo');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:10,1')->name('register.store');

    if (DevLoginController::enabled()) {
        Route::post('/auth/dev', DevLoginController::class)->name('auth.dev');
    }
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
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/login', [LoginSettingsController::class, 'update'])->middleware('throttle:10,1')->name('settings.login');
    Route::post('/logout', [PasswordLoginController::class, 'logout'])->name('logout');
    Route::post('/demo/exit', [DemoController::class, 'exit'])->name('demo.exit');

    Route::get('/books', [BookController::class, 'index'])->name('books');
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    Route::patch('/books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    Route::post('/books/{book}/move', [BookController::class, 'move'])->name('books.move');
    Route::post('/books/{book}/log', [BookController::class, 'log'])->name('books.log');
    Route::post('/books/{book}/pdf', [BookReaderController::class, 'upload'])->middleware('throttle:20,1')->name('books.pdf.upload');
    Route::get('/books/{book}/pdf', [BookReaderController::class, 'file'])->name('books.pdf');
    Route::delete('/books/{book}/pdf', [BookReaderController::class, 'removePdf'])->name('books.pdf.remove');
    Route::get('/books/{book}/read', [BookReaderController::class, 'show'])->name('books.read');
    Route::put('/books/{book}/progress', [BookReaderController::class, 'progress'])->middleware('throttle:120,1')->name('books.progress');
    Route::post('/books/{book}/session', [BookReaderController::class, 'startSession'])->name('books.session');

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

Route::post('/locale', LocaleController::class)->middleware('throttle:30,1')->name('locale');

Route::post('/telegram/webhook', WebhookController::class)
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->name('telegram.webhook');
