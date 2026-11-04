<?php

use App\Models\User;
use App\Modules\Planner\Task;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    // Toshkent: 2026-11-04 10:00
    Carbon::setTestNow(Carbon::parse('2026-11-04 05:00:00', 'UTC'));
    $this->user = User::factory()->create(['timezone' => 'Asia/Tashkent']);
    $this->actingAs($this->user);
});

it('adds a task to the chosen day', function () {
    $this->post('/tasks', ['title' => 'Kurs ishini yozish', 'date' => '2026-11-04', 'start_time' => '14:00', 'end_time' => '15:30'])
        ->assertRedirect();

    $task = Task::sole();
    expect($task->user_id)->toBe($this->user->id)
        ->and($task->toCard())->toMatchArray([
            'title' => 'Kurs ishini yozish',
            'date' => '2026-11-04',
            'start_time' => '14:00',
            'end_time' => '15:30',
            'done' => false,
        ]);
});

it('validates title and time order', function () {
    $this->post('/tasks', ['title' => '', 'date' => '2026-11-04'])->assertSessionHasErrors('title');
    $this->post('/tasks', ['title' => 'X', 'date' => '2026-11-04', 'start_time' => '15:00', 'end_time' => '14:00'])
        ->assertSessionHasErrors('end_time');

    expect(Task::count())->toBe(0);
});

it('shows today\'s tasks with progress, timed tasks first', function () {
    Task::factory()->for($this->user)->create(['title' => 'Vaqtsiz', 'date' => '2026-11-04']);
    Task::factory()->for($this->user)->create(['title' => 'Kechki', 'date' => '2026-11-04', 'start_time' => '18:00']);
    Task::factory()->for($this->user)->done()->create(['title' => 'Ertalabki', 'date' => '2026-11-04', 'start_time' => '08:00']);
    Task::factory()->for($this->user)->create(['title' => 'Ertangi', 'date' => '2026-11-05']);

    $this->get('/')->assertInertia(fn (Assert $page) => $page
        ->component('Today')
        ->where('date', '2026-11-04')
        ->where('stats', ['done' => 1, 'total' => 3])
        ->where('tasks.0.title', 'Ertalabki')
        ->where('tasks.1.title', 'Kechki')
        ->where('tasks.2.title', 'Vaqtsiz')
    );

    $this->get('/?date=2026-11-05')->assertInertia(fn (Assert $page) => $page
        ->where('date', '2026-11-05')
        ->has('tasks', 1)
    );
});

it('uses the user\'s timezone for today', function () {
    // UTC 20:30 = Toshkent 01:30 ertasi kuni
    Carbon::setTestNow(Carbon::parse('2026-11-04 20:30:00', 'UTC'));

    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('today', '2026-11-05'));
});

it('checks and unchecks a task', function () {
    $task = Task::factory()->for($this->user)->create();

    $this->post("/tasks/{$task->id}/toggle");
    expect($task->fresh()->isDone())->toBeTrue();

    $this->post("/tasks/{$task->id}/toggle");
    expect($task->fresh()->isDone())->toBeFalse();
});

it('moves an undone task to tomorrow and remembers where it came from', function () {
    $task = Task::factory()->for($this->user)->create(['date' => '2026-11-04']);

    $this->post("/tasks/{$task->id}/move-tomorrow");
    $this->post("/tasks/{$task->id}/move-tomorrow");

    $task->refresh();
    expect($task->date->toDateString())->toBe('2026-11-06')
        ->and($task->moved_from_date->toDateString())->toBe('2026-11-04');
});

it('carries undone tasks from past days over to today', function () {
    $old = Task::factory()->for($this->user)->create(['date' => '2026-11-02']);
    $doneOld = Task::factory()->for($this->user)->done()->create(['date' => '2026-11-02']);

    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('overdue', 1));
    $this->post('/tasks/carry-over');

    expect($old->fresh()->date->toDateString())->toBe('2026-11-04')
        ->and($doneOld->fresh()->date->toDateString())->toBe('2026-11-02');
});

it('never lets a user see or change another user\'s task', function () {
    $other = Task::factory()->create(['title' => 'Begona', 'date' => '2026-11-04']);

    $this->get('/')->assertInertia(fn (Assert $page) => $page->has('tasks', 0));
    $this->post("/tasks/{$other->id}/toggle")->assertNotFound();
    $this->patch("/tasks/{$other->id}", ['title' => 'x', 'date' => '2026-11-04'])->assertNotFound();
    $this->delete("/tasks/{$other->id}")->assertNotFound();

    expect($other->fresh()->title)->toBe('Begona')->and($other->fresh()->isDone())->toBeFalse();
});

it('edits and deletes a task', function () {
    $task = Task::factory()->for($this->user)->create();

    $this->patch("/tasks/{$task->id}", ['title' => 'Yangi nom', 'date' => '2026-11-05'])->assertRedirect();
    expect($task->fresh()->title)->toBe('Yangi nom');

    $this->delete("/tasks/{$task->id}");
    expect(Task::count())->toBe(0);
});
