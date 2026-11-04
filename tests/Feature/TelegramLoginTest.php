<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\TelegramInitData;

beforeEach(function () {
    config(['telegram.bot_token' => '123456:TEST-token']);
});

function initDataFor(array $user): string
{
    return TelegramInitData::make('123456:TEST-token', $user);
}

it('sends guests to the login page', function () {
    $this->get('/')->assertRedirect('/login');

    $this->get('/login')->assertInertia(fn (Assert $page) => $page->component('Login'));
});

it('creates the user on first Mini App login', function () {
    $this->post('/auth/telegram/webapp', [
        'init_data' => initDataFor(['id' => 555, 'first_name' => 'Madina', 'last_name' => 'Karimova', 'username' => 'madina']),
    ])->assertRedirect('/');

    $user = User::firstWhere('telegram_id', 555);
    expect($user)->not->toBeNull()
        ->and($user->first_name)->toBe('Madina')
        ->and($user->telegram_username)->toBe('madina')
        ->and($user->onboarding_step)->toBe('name');
    $this->assertAuthenticatedAs($user);
});

it('logs in an existing user without overwriting their name', function () {
    $user = User::factory()->create(['telegram_id' => 556, 'first_name' => 'Aziz']);

    $this->post('/auth/telegram/webapp', [
        'init_data' => initDataFor(['id' => 556, 'first_name' => 'Telegramdagi ism']),
    ])->assertRedirect('/');

    expect($user->fresh()->first_name)->toBe('Aziz')
        ->and(User::count())->toBe(1);
    $this->assertAuthenticatedAs($user);
});

it('rejects a forged login', function () {
    $forged = TelegramInitData::make('999:attacker', ['id' => 557, 'first_name' => 'X']);

    $this->post('/auth/telegram/webapp', ['init_data' => $forged])
        ->assertSessionHasErrors('init_data');

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

it('shows the Today page to a logged in user', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page->component('Today')->has('auth.user'));
});
