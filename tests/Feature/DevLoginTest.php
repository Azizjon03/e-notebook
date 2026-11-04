<?php

use App\Models\User;
use App\Modules\Core\Dev\DevLoginController;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

it('is off outside the local environment, even with DEV_LOGIN=true', function () {
    config(['app.dev_login' => true]);

    expect(DevLoginController::enabled())->toBeFalse(); // APP_ENV=testing
    $this->post('/auth/dev', ['gender' => 'male'])->assertNotFound();
    $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('devLogin', false));
});

it('is off locally unless DEV_LOGIN is set', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['app.dev_login' => false]);
    expect(DevLoginController::enabled())->toBeFalse();

    config(['app.dev_login' => true]);
    expect(DevLoginController::enabled())->toBeTrue();
});

it('logs in a finished test user of the chosen gender', function () {
    Route::post('/_test/auth/dev', DevLoginController::class)->middleware('web');
    $this->seed();

    $this->post('/_test/auth/dev', ['gender' => 'female'])->assertRedirect(route('today'));
    $this->post('/_test/auth/dev', ['gender' => 'female']);

    $user = User::where('telegram_id', DevLoginController::TELEGRAM_IDS['female'])->sole();
    expect($user->gender)->toBe('female')
        ->and($user->hasFinishedOnboarding())->toBeTrue()
        ->and($user->region?->name)->toBe('Toshkent shahri');
    $this->assertAuthenticatedAs($user);
});
