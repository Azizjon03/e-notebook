<?php

use App\Models\User;
use App\Modules\Planner\Task;
use App\Modules\Routine\DailyEntry;
use App\Modules\Routine\Habit;
use App\Modules\Routine\HabitDefaults;
use App\Modules\Study\StudySession;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 12:00', 'Asia/Tashkent'));
    $this->user = User::factory()->create();
    HabitDefaults::ensure($this->user);
    $this->actingAs($this->user);
});

it('shows 7 day statistics', function () {
    Task::factory()->for($this->user)->done()->create(['date' => '2026-11-04']);
    Task::factory()->for($this->user)->create(['date' => '2026-11-04']);
    Task::factory()->for($this->user)->done()->create(['date' => '2026-11-01']);
    Task::factory()->for($this->user)->done()->create(['date' => '2026-10-20']); // oraliqdan tashqarida
    $fajr = Habit::where('key', 'fajr')->value('id');
    DailyEntry::create(['habit_id' => $fajr, 'date' => '2026-11-04', 'done_at' => now()]);
    StudySession::create(['area_id' => 1, 'started_at' => now()->subHours(2), 'ended_at' => now()->subHour(), 'minutes' => 55]);

    $this->get('/stats')->assertInertia(fn (Assert $page) => $page
        ->component('Stats')
        ->where('range', 7)
        ->has('days', 7)
        ->where('days.6.date', '2026-11-04')
        ->where('days.6.tasks_done', 1)
        ->where('days.6.tasks_total', 2)
        ->where('days.6.prayers', 1)
        ->where('days.6.study', 55)
        ->where('summary.tasks_rate', 67)
        ->where('summary.prayers_possible', 35)
        ->where('habits.0.name', 'Qadam')
        ->where('habits.0.scheduled', 7)
    );

    $this->get('/stats?range=30')->assertInertia(fn (Assert $page) => $page->where('range', 30)->has('days', 30));
});

it('does not count other users in statistics', function () {
    Task::factory()->done()->create(['date' => '2026-11-04']);

    $this->get('/stats')->assertInertia(fn (Assert $page) => $page->where('summary.tasks_done', 0)->where('summary.tasks_rate', null));
});

it('backs up PostgreSQL with pg_dump without putting the password on the command line', function () {
    config([
        'database.connections.backup_test' => ['driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => 5432, 'database' => 'enotebook', 'username' => 'enotebook', 'password' => 's3cret'],
    ]);
    Process::fake();

    $this->artisan('backup:database', ['--connection' => 'backup_test'])->assertSuccessful();

    Process::assertRan(function (PendingProcess $process) {
        $command = implode(' ', (array) $process->command);

        return str_contains($command, 'pg_dump')
            && str_contains($command, '--format=custom')
            && ! str_contains($command, 's3cret')
            && $process->environment['PGPASSWORD'] === 's3cret';
    });
});

it('refuses to back up a non-PostgreSQL database', function () {
    config(['database.connections.backup_test' => ['driver' => 'sqlite', 'database' => ':memory:']]);
    Process::fake();

    $this->artisan('backup:database', ['--connection' => 'backup_test'])->assertFailed();
    Process::assertNothingRan();
});
