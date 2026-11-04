<?php

use App\Models\User;
use App\Modules\Core\Auth\Phone;
use App\Modules\Geo\Region;
use Illuminate\Support\Facades\Queue;

beforeEach(fn () => $this->seed());

function registration(array $overrides = []): array
{
    return $overrides + [
        'first_name' => 'Ali',
        'last_name' => 'Valiyev',
        'phone' => '+998 90 123 45 67',
        'password' => 'secret12',
        'password_confirmation' => 'secret12',
        'gender' => 'male',
        'region_id' => Region::whereNull('parent_id')->value('id'),
    ];
}

it('normalizes Uzbek phone numbers', function () {
    expect(Phone::normalize('+998 (90) 123-45-67'))->toBe('+998901234567')
        ->and(Phone::normalize('901234567'))->toBe('+998901234567')
        ->and(Phone::normalize('12345'))->toBeNull();
});

it('registers with phone and password and logs in', function () {
    $this->post('/register', registration())->assertRedirect(route('today'));

    $user = User::firstWhere('phone', '+998901234567');
    expect($user)->not->toBeNull()
        ->and($user->telegram_id)->toBeNull()
        ->and($user->hasFinishedOnboarding())->toBeTrue()
        ->and($user->password)->not->toBe('secret12');
    $this->assertAuthenticatedAs($user);
});

it('rejects a short password and a taken phone', function () {
    $this->post('/register', registration(['password' => 'short1', 'password_confirmation' => 'short1']))
        ->assertSessionHasErrors('password');

    User::factory()->create(['phone' => '+998901234567']);
    $this->post('/register', registration())->assertSessionHasErrors('phone');
    $this->assertGuest();
});

it('logs in with the right password only', function () {
    $user = User::factory()->create(['phone' => '+998901234567', 'password' => 'secret12']);

    $this->post('/login', ['phone' => '90 123 45 67', 'password' => 'wrongpass'])->assertSessionHasErrors('phone');
    $this->assertGuest();

    $this->post('/login', ['phone' => '+998 90 123 45 67', 'password' => 'secret12'])->assertRedirect(route('today'));
    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('lets a Telegram user set a password, then requires the current one to change it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/settings/login', ['phone' => '+998901112233', 'password' => 'newpass12', 'password_confirmation' => 'newpass12'])
        ->assertSessionHasNoErrors();
    expect($user->fresh()->phone)->toBe('+998901112233');

    $this->actingAs($user)->put('/settings/login', ['phone' => '+998901112233', 'current_password' => 'nope', 'password' => 'other123', 'password_confirmation' => 'other123'])
        ->assertSessionHasErrors('current_password');
});

it('skips bot reminders for users without Telegram', function () {
    Queue::fake();
    User::factory()->create(['telegram_id' => null, 'phone' => '+998901234567', 'day_summary' => true]);
    $this->travelTo(now('Asia/Tashkent')->setTime(21, 30));

    $this->artisan('reminders:dispatch')->assertSuccessful();
    Queue::assertNothingPushed();
});
