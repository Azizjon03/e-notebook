<?php

use App\Models\User;
use App\Modules\Planner\Recurrence;
use App\Modules\Planner\Task;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // Toshkent: 2026-11-04 (chorshanba) 10:00
    Carbon::setTestNow(Carbon::parse('2026-11-04 05:00:00', 'UTC'));
    $this->user = User::factory()->create(['timezone' => 'Asia/Tashkent']);
    $this->actingAs($this->user);
});

function taskDates(): array
{
    return Task::orderBy('date')->pluck('date')->map->toDateString()->all();
}

it('creates a weekly recurring task and its upcoming copies', function () {
    // Zal: Du, Cho, Ju
    $this->post('/tasks', ['title' => 'Zal', 'date' => '2026-11-04', 'start_time' => '18:00', 'repeat' => 'weekly', 'weekdays' => [1, 3, 5]])
        ->assertRedirect();

    $rule = Recurrence::sole();
    expect($rule->describe())->toBe('Har hafta: Du, Cho, Ju')
        ->and(taskDates())->toBe(['2026-11-04', '2026-11-06', '2026-11-09'])
        ->and(Task::first()->toCard())->toMatchArray(['start_time' => '18:00', 'recurring' => true]);
});

it('requires weekdays for a weekly rule', function () {
    $this->post('/tasks', ['title' => 'Zal', 'date' => '2026-11-04', 'repeat' => 'weekly'])
        ->assertSessionHasErrors('weekdays');

    expect(Recurrence::count())->toBe(0);
});

it('fills recurring copies when a future day is opened, without duplicates', function () {
    Recurrence::create(['title' => 'Kitob o\'qish', 'frequency' => 'daily', 'starts_on' => '2026-11-04']);

    $this->get('/?date=2026-11-20')->assertInertia(fn (Assert $page) => $page->where('tasks.0.title', 'Kitob o\'qish'));
    $this->get('/?date=2026-11-20');

    expect(Task::whereDate('date', '2026-11-20')->count())->toBe(1);
});

it('does not create copies in the past or too far ahead', function () {
    Recurrence::create(['title' => 'Har kuni', 'frequency' => 'daily', 'starts_on' => '2026-10-01']);

    $this->get('/?date=2026-10-15')->assertInertia(fn (Assert $page) => $page->has('tasks', 0));
    $this->get('/?date=2027-06-01')->assertInertia(fn (Assert $page) => $page->has('tasks', 0));
});

it('handles monthly rules on short months', function () {
    $rule = new Recurrence(['frequency' => 'monthly', 'starts_on' => '2026-01-31']);

    expect($rule->occursOn(Carbon::parse('2026-02-28')->toImmutable()))->toBeTrue()
        ->and($rule->occursOn(Carbon::parse('2026-03-31')->toImmutable()))->toBeTrue()
        ->and($rule->occursOn(Carbon::parse('2026-03-30')->toImmutable()))->toBeFalse();
});

it('deletes one copy without bringing it back', function () {
    $this->post('/tasks', ['title' => 'Yugurish', 'date' => '2026-11-04', 'repeat' => 'daily']);
    $copy = Task::whereDate('date', '2026-11-05')->sole();

    $this->delete("/tasks/{$copy->id}");
    $this->get('/?date=2026-11-05')->assertInertia(fn (Assert $page) => $page->has('tasks', 0));
    $this->get('/?date=2026-11-06')->assertInertia(fn (Assert $page) => $page->has('tasks', 1));
});

it('stops a rule from a day on, keeping finished history', function () {
    $this->post('/tasks', ['title' => 'Yugurish', 'date' => '2026-11-04', 'repeat' => 'daily']);
    Task::whereDate('date', '2026-11-04')->sole()->update(['done_at' => now()]);
    $copy = Task::whereDate('date', '2026-11-06')->sole();

    $this->delete("/tasks/{$copy->id}?scope=all");

    expect(taskDates())->toBe(['2026-11-04', '2026-11-05'])
        ->and(Recurrence::sole()->until->toDateString())->toBe('2026-11-05');
    $this->get('/?date=2026-11-08')->assertInertia(fn (Assert $page) => $page->has('tasks', 0));
});

it('moves a recurring copy to tomorrow as a plain task', function () {
    $this->post('/tasks', ['title' => 'Yugurish', 'date' => '2026-11-04', 'repeat' => 'daily']);
    $today = Task::whereDate('date', '2026-11-04')->sole();

    $this->post("/tasks/{$today->id}/move-tomorrow")->assertRedirect();

    expect(Task::whereDate('date', '2026-11-05')->count())->toBe(2)
        ->and($today->fresh()->recurrence_id)->toBeNull();
    $this->get('/?date=2026-11-04')->assertInertia(fn (Assert $page) => $page->has('tasks', 0));
});

it('shows a month grid with per-day counts', function () {
    Task::factory()->for($this->user)->create(['date' => '2026-11-10']);
    Task::factory()->for($this->user)->done()->create(['date' => '2026-11-10']);
    Task::factory()->create(['date' => '2026-11-10']); // boshqa foydalanuvchi

    $this->get('/calendar?date=2026-11-15')->assertInertia(fn (Assert $page) => $page
        ->component('Calendar')
        ->where('view', 'month')
        ->where('month', '2026-11')
        ->has('days', 42) // 26-okt (Du) .. 6-dek (Ya)
        ->where('days.0.date', '2026-10-26')
        ->where('days.15.date', '2026-11-10')
        ->where('days.15.total', 2)
        ->where('days.15.done', 1)
    );
});

it('shows a week with full task cards and the recurring list', function () {
    $this->post('/tasks', ['title' => 'Zal', 'date' => '2026-11-04', 'repeat' => 'weekly', 'weekdays' => [1, 3, 5]]);

    $this->get('/calendar?view=week&date=2026-11-04')->assertInertia(fn (Assert $page) => $page
        ->where('view', 'week')
        ->has('days', 7)
        ->where('days.0.date', '2026-11-02')
        ->where('days.2.tasks.0.title', 'Zal')
        ->where('days.4.tasks.0.title', 'Zal')
        ->where('recurrences.0.rule', 'Har hafta: Du, Cho, Ju')
    );
});
