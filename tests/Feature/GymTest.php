<?php

use App\Models\User;
use App\Modules\Routine\DailyEntry;
use App\Modules\Routine\Habit;
use App\Modules\Sport\GymCatalog;
use App\Modules\Sport\GymWorkout;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 05:00:00', 'UTC')); // Toshkent: chorshanba 10:00
});

function gymUser(?string $gender): User
{
    $user = User::factory()->create(['timezone' => 'Asia/Tashkent', 'gender' => $gender]);
    test()->actingAs($user);

    return $user;
}

it('has programs for both genders, each with exactly one recommended', function () {
    foreach (['male', 'female'] as $gender) {
        $programs = GymCatalog::forGender($gender);
        expect(count($programs))->toBeGreaterThanOrEqual(3)
            ->and(collect($programs)->where('recommended', true))->toHaveCount(1)
            ->and(collect($programs)->every(fn ($p) => $p['gender'] === $gender))->toBeTrue();
    }
    expect(GymCatalog::forGender(null))->toBe([]);
});

it('shows a woman only women\'s programs', function () {
    gymUser('female');

    $this->get('/gym')->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Gym')
        ->where('programs', fn ($programs) => collect($programs)->every(fn ($p) => str_starts_with($p['key'], 'f_'))
            && collect($programs)->isNotEmpty()));
});

it('shows a man only men\'s programs', function () {
    gymUser('male');

    $this->get('/gym')->assertInertia(fn (Assert $page) => $page
        ->where('programs', fn ($programs) => collect($programs)->every(fn ($p) => str_starts_with($p['key'], 'm_'))
            && collect($programs)->isNotEmpty()));
});

it('shows no programs until gender is chosen, and gender can be set once', function () {
    $user = gymUser(null);

    $this->get('/gym')->assertInertia(fn (Assert $page) => $page->where('gender', null)->has('programs', 0));

    $this->post('/gym/gender', ['gender' => 'female'])->assertRedirect();
    expect($user->fresh()->gender)->toBe('female');

    $this->post('/gym/gender', ['gender' => 'male'])->assertStatus(422);
    expect($user->fresh()->gender)->toBe('female');
});

it('refuses a program meant for the other gender', function () {
    $woman = gymUser('female');
    $this->post('/gym/program', ['program' => 'm_ppl_6'])->assertSessionHasErrors('program');
    expect($woman->fresh()->gym_program)->toBeNull();

    $man = gymUser('male');
    $this->post('/gym/program', ['program' => 'f_lower_4'])->assertSessionHasErrors('program');
    $this->post('/gym/program', ['program' => 'nope'])->assertSessionHasErrors('program');
    expect($man->fresh()->gym_program)->toBeNull();
});

it('hides a saved program if it does not match the user\'s gender', function () {
    $user = gymUser('female');
    $user->forceFill(['gym_program' => 'm_full_3'])->save();

    $this->get('/gym')->assertInertia(fn (Assert $page) => $page->where('current', null)->where('workout', null));
    $this->post('/gym/finish')->assertStatus(422);
});

it('choosing a program turns on the gym habit with the program days', function () {
    $user = gymUser('male');

    $this->post('/gym/program', ['program' => 'm_upper_lower_4'])->assertRedirect();

    $habit = Habit::where('key', 'gym')->sole();
    expect($user->fresh()->gym_program)->toBe('m_upper_lower_4')
        ->and($habit->is_active)->toBeTrue()
        ->and($habit->weekdays)->toBe([1, 2, 4, 5]);

    $this->get('/gym')->assertInertia(fn (Assert $page) => $page
        ->where('current', 'm_upper_lower_4')
        ->where('workout.day.name', 'Yuqori 1')
        ->where('workout.scheduled_today', false)); // chorshanba
});

it('records sets, finishes the workout, marks the habit and moves to the next day', function () {
    $user = gymUser('female');
    $this->post('/gym/program', ['program' => 'f_full_3']);

    $this->post('/gym/set', ['exercise' => 0, 'done' => 2])->assertRedirect();
    $this->post('/gym/set', ['exercise' => 1, 'done' => 99])->assertRedirect(); // set sonidan oshmaydi
    $this->post('/gym/set', ['exercise' => 50, 'done' => 1])->assertSessionHasErrors('exercise');

    $workout = GymWorkout::sole();
    $maxSets = GymCatalog::PROGRAMS['f_full_3']['days'][0]['exercises'][1][1];
    expect($workout->completed_sets)->toBe(['0' => 2, '1' => $maxSets]);

    $this->post('/gym/finish')->assertRedirect();
    $this->post('/gym/finish')->assertRedirect(); // ikkinchi marta hech narsa qilmaydi

    $habit = Habit::where('key', 'gym')->sole();
    expect($workout->fresh()->finished_at)->not->toBeNull()
        ->and($user->fresh()->gym_day_index)->toBe(1)
        ->and(DailyEntry::where('habit_id', $habit->id)->whereNotNull('done_at')->count())->toBe(1)
        ->and((int) DB::table('points_ledger')->where('user_id', $user->id)->sum('points'))->toBe((int) config('points.gym'));

    $this->post('/gym/set', ['exercise' => 0, 'done' => 3])->assertStatus(422);

    // Ertaga: dasturning keyingi kuni
    Carbon::setTestNow(Carbon::parse('2026-11-06 05:00:00', 'UTC'));
    $this->get('/gym')->assertInertia(fn (Assert $page) => $page
        ->where('workout.day_index', 1)
        ->where('workout.finished', false));
});

it('keeps each user\'s workouts private', function () {
    gymUser('male');
    $this->post('/gym/program', ['program' => 'm_full_3']);
    $this->post('/gym/set', ['exercise' => 0, 'done' => 1]);

    $other = gymUser('male');
    $this->post('/gym/program', ['program' => 'm_full_3']);
    $this->get('/gym')->assertInertia(fn (Assert $page) => $page->where('workout.completed', []));
    expect(GymWorkout::withoutGlobalScope('owner')->count())->toBe(1)
        ->and(GymWorkout::count())->toBe(0);
});
