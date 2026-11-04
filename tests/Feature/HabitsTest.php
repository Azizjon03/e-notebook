<?php

use App\Models\User;
use App\Modules\Routine\DailyEntry;
use App\Modules\Routine\Habit;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 05:00:00', 'UTC')); // Toshkent: chorshanba 10:00
    $this->user = User::factory()->create(['timezone' => 'Asia/Tashkent']);
    $this->actingAs($this->user);
});

function habit(string $key): Habit
{
    return Habit::where('key', $key)->sole();
}

it('creates default habits once: prayers and steps on, gym off', function () {
    $this->get('/');
    $this->get('/');

    expect(Habit::count())->toBe(7)
        ->and(Habit::where('is_active', true)->orderBy('sort')->pluck('key')->all())->toBe(['fajr', 'dhuhr', 'asr', 'maghrib', 'isha', 'steps'])
        ->and(habit('gym')->weekdays)->toBe([1, 3, 5])
        ->and(habit('steps')->target)->toBe(10000);
});

it('shows only active habits scheduled for the day', function () {
    $this->get('/');
    habit('gym')->update(['is_active' => true]);

    // Chorshanba: zal bor
    $this->get('/')->assertInertia(fn (Assert $page) => $page->has('habits', 7));
    // Payshanba: zal yo'q
    $this->get('/?date=2026-11-05')->assertInertia(fn (Assert $page) => $page->has('habits', 6));
});

it('marks and unmarks a prayer', function () {
    $this->get('/');
    $fajr = habit('fajr');

    $this->post("/habits/{$fajr->id}/entry", ['date' => '2026-11-04'])->assertRedirect();
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('habits.0.key', 'fajr')->where('habits.0.done', true));

    $this->post("/habits/{$fajr->id}/entry", ['date' => '2026-11-04']);
    expect(DailyEntry::sole()->done_at)->toBeNull();
});

it('records steps and counts the habit done only at the target', function () {
    $this->get('/');
    $steps = habit('steps');

    $this->post("/habits/{$steps->id}/entry", ['date' => '2026-11-04', 'value' => 6500]);
    expect(DailyEntry::sole()->only('value', 'done_at'))->toBe(['value' => 6500, 'done_at' => null]);

    $this->post("/habits/{$steps->id}/entry", ['date' => '2026-11-04', 'value' => 10400]);
    expect(DailyEntry::sole()->done_at)->not->toBeNull();

    $this->post("/habits/{$steps->id}/entry", ['date' => '2026-11-04'])->assertSessionHasErrors('value');
});

it('refuses to mark a future day', function () {
    $this->get('/');

    $this->post('/habits/'.habit('fajr')->id.'/entry', ['date' => '2026-11-05'])->assertStatus(422);
    expect(DailyEntry::count())->toBe(0);
});

it('turns gym on with chosen days and changes the steps target', function () {
    $this->get('/habits');

    $this->patch('/habits/'.habit('gym')->id, ['is_active' => true, 'weekdays' => [2, 4, 6]]);
    $this->patch('/habits/'.habit('steps')->id, ['target' => 8000]);

    expect(habit('gym')->is_active)->toBeTrue()
        ->and(habit('gym')->weekdays)->toBe([2, 4, 6])
        ->and(habit('steps')->target)->toBe(8000);
});

it('adds and removes a custom habit, but keeps system habits', function () {
    $this->get('/habits');

    $this->post('/habits', ['name' => 'Qur\'on o\'qish', 'area' => 'ilm', 'kind' => 'duration', 'target' => 20])->assertRedirect();
    $custom = Habit::whereNull('key')->sole();
    expect($custom->area->key)->toBe('ilm')->and($custom->weekdays)->toBeNull();

    $this->delete("/habits/{$custom->id}");
    expect(Habit::whereNull('key')->count())->toBe(0);

    $this->delete('/habits/'.habit('fajr')->id)->assertStatus(422);
    $this->post('/habits', ['name' => 'X', 'area' => 'sport', 'kind' => 'count'])->assertSessionHasErrors('target');
});

it('shows the last 7 days on the habits page', function () {
    $this->get('/habits');
    DailyEntry::create(['habit_id' => habit('fajr')->id, 'date' => '2026-11-03', 'done_at' => now()]);
    DailyEntry::create(['habit_id' => habit('fajr')->id, 'date' => '2026-11-04', 'done_at' => now()]);

    $this->get('/habits')->assertInertia(fn (Assert $page) => $page
        ->component('Habits')
        ->where('habits.0.key', 'fajr')
        ->where('habits.0.week', [false, false, false, false, false, true, true])
    );
});

it('keeps habits private to their owner', function () {
    $other = User::factory()->create();
    $this->actingAs($other)->get('/');
    $foreign = Habit::withoutGlobalScope('owner')->where('user_id', $other->id)->where('key', 'fajr')->sole();

    $this->actingAs($this->user)->post("/habits/{$foreign->id}/entry", ['date' => '2026-11-04'])->assertNotFound();
    $this->patch("/habits/{$foreign->id}", ['is_active' => false])->assertNotFound();
});
