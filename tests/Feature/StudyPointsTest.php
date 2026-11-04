<?php

use App\Models\User;
use App\Modules\Core\Points;
use App\Modules\Routine\DailyEntry;
use App\Modules\Routine\Habit;
use App\Modules\Routine\HabitPoints;
use App\Modules\Study\StudySession;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 14:00:00', 'UTC')); // Toshkent 19:00
    $this->user = User::factory()->create(['timezone' => 'Asia/Tashkent']);
    $this->actingAs($this->user);
});

function total(User $user): int
{
    return app(Points::class)->total($user);
}

it('computes levels: 100, then 1.5x more each level', function () {
    expect(Points::level(0))->toBe(['level' => 0, 'total' => 0, 'into' => 0, 'needed' => 100])
        ->and(Points::level(99)['level'])->toBe(0)
        ->and(Points::level(100))->toMatchArray(['level' => 1, 'into' => 0, 'needed' => 150])
        ->and(Points::level(250))->toMatchArray(['level' => 2, 'into' => 0, 'needed' => 225])
        ->and(Points::level(500))->toMatchArray(['level' => 3, 'into' => 25]);
});

it('runs a 50 minute study session and gives 10 points', function () {
    $this->post('/study/start', ['title' => 'DDIA 5-bob', 'area' => 'study', 'planned_minutes' => 50])->assertRedirect();
    $session = StudySession::sole();

    $this->get('/study')->assertInertia(fn (Assert $page) => $page->component('Study')->where('running.id', $session->id));

    Carbon::setTestNow(now()->addMinutes(50));
    $this->post("/study/{$session->id}/stop");

    expect($session->fresh()->minutes)->toBe(50)->and(total($this->user))->toBe(10);

    // Qayta to'xtatish ball qo'shmaydi
    $this->post("/study/{$session->id}/stop");
    expect(total($this->user))->toBe(10);

    $this->get('/study')->assertInertia(fn (Assert $page) => $page
        ->where('running', null)
        ->where('today.0.minutes', 50)
        ->where('week.6.minutes', 50)
        ->where('level.total', 10)
    );
});

it('gives partial points for a short session and caps a forgotten timer', function () {
    expect(StudySession::pointsFor(23))->toBe(4)->and(StudySession::pointsFor(45))->toBe(10);

    $this->post('/study/start', ['area' => 'study', 'planned_minutes' => 25]);
    Carbon::setTestNow(now()->addHours(9));
    $this->post('/study/'.StudySession::sole()->id.'/stop');

    expect(StudySession::sole()->minutes)->toBe(240);
});

it('closes a running session when a new one starts', function () {
    $this->post('/study/start', ['area' => 'study', 'planned_minutes' => 50]);
    Carbon::setTestNow(now()->addMinutes(30));
    $this->post('/study/start', ['area' => 'ilm', 'planned_minutes' => 25]);

    expect(StudySession::whereNull('ended_at')->count())->toBe(1)
        ->and(StudySession::orderBy('id')->first()->minutes)->toBe(30);
});

it('removes points when a session is deleted', function () {
    $this->post('/study/start', ['area' => 'study', 'planned_minutes' => 50]);
    Carbon::setTestNow(now()->addMinutes(50));
    $session = StudySession::sole();
    $this->post("/study/{$session->id}/stop");

    $this->delete("/study/{$session->id}");

    expect(total($this->user))->toBe(0)->and(StudySession::count())->toBe(0);
});

it('awards and takes back habit points', function () {
    $this->get('/');
    $fajr = Habit::where('key', 'fajr')->sole();
    $steps = Habit::where('key', 'steps')->sole();

    $this->post("/habits/{$fajr->id}/entry", ['date' => '2026-11-04']);
    $this->post("/habits/{$steps->id}/entry", ['date' => '2026-11-04', 'value' => 5000]);
    expect(total($this->user))->toBe(2);

    $this->post("/habits/{$steps->id}/entry", ['date' => '2026-11-04', 'value' => 12000]);
    expect(total($this->user))->toBe(5);

    $this->post("/habits/{$fajr->id}/entry", ['date' => '2026-11-04']);
    expect(total($this->user))->toBe(3);
});

it('gives a streak bonus on the 7th day in a row', function () {
    $this->get('/');
    $fajr = Habit::where('key', 'fajr')->sole();
    foreach (range(6, 1) as $i) {
        DailyEntry::create(['habit_id' => $fajr->id, 'date' => now('Asia/Tashkent')->subDays($i)->toDateString(), 'done_at' => now()]);
    }

    $this->post("/habits/{$fajr->id}/entry", ['date' => '2026-11-04']);

    expect(DB::table('points_ledger')->where('reason', 'streak')->sum('points'))->toEqual(20)
        ->and(total($this->user))->toBe(22);
});

it('counts the gym streak only on gym days', function () {
    $this->get('/');
    $gym = Habit::where('key', 'gym')->sole();
    $gym->update(['is_active' => true]); // Du, Cho, Ju

    // 2026-11-04 chorshanba; oldingi zal kunlari: 2-noy (Du), 30-okt (Ju), 28-okt (Cho)
    foreach (['2026-11-02', '2026-10-30', '2026-10-28'] as $d) {
        DailyEntry::create(['habit_id' => $gym->id, 'date' => $d, 'done_at' => now()]);
    }

    expect(app(HabitPoints::class)->streak($gym, Carbon::parse('2026-11-02')->toImmutable()))->toBe(3);
});

it('shows the level on the Today page', function () {
    DB::table('points_ledger')->insert(['user_id' => $this->user->id, 'source_type' => 'test', 'source_id' => 1, 'reason' => 'x', 'points' => 120, 'created_at' => now()]);

    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('level.level', 1)->where('level.total', 120));
});

it('keeps sessions private', function () {
    $other = User::factory()->create();
    $session = StudySession::withoutGlobalScope('owner')->create(['user_id' => $other->id, 'area_id' => 1, 'started_at' => now()]);

    $this->post("/study/{$session->id}/stop")->assertNotFound();
    $this->delete("/study/{$session->id}")->assertNotFound();
});
