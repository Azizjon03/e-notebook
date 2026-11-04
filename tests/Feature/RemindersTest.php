<?php

use App\Models\User;
use App\Modules\Core\Reminders\SendReminder;
use App\Modules\Geo\Region;
use App\Modules\Planner\Task;
use App\Modules\Routine\Habit;
use App\Modules\Routine\HabitDefaults;
use App\Modules\Routine\PrayerTime;
use App\Modules\Telegram\TelegramClient;
use Database\Seeders\RegionSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RegionSeeder::class);
    config(['app.url' => 'https://enotebook.test', 'telegram.bot_token' => '123456:TEST-token']);
    Queue::fake();

    $region = Region::firstWhere('name', 'Toshkent shahri');
    $this->user = User::factory()->create([
        'telegram_id' => 5005,
        'region_id' => $region->id,
        'planning_mode' => 'evening',
        'planning_time' => '22:00',
        'weekly_planning_day' => 7,
        'weekly_planning_time' => '20:00',
    ]);
    HabitDefaults::ensure($this->user);
    PrayerTime::create(['region_id' => $region->id, 'date' => '2026-11-04', 'fajr' => '05:41', 'sunrise' => '07:05', 'dhuhr' => '12:05', 'asr' => '15:10', 'maghrib' => '16:58', 'isha' => '18:20']);
});

/** Toshkent vaqti bilan soatni o'rnatib, dispatch'ni ishga tushiradi. */
function runAt(string $tashkentTime): void
{
    Carbon::setTestNow(Carbon::parse($tashkentTime, 'Asia/Tashkent'));
    test()->artisan('reminders:dispatch')->assertSuccessful();
}

function sentTextsContaining(string $needle): int
{
    return Queue::pushed(SendReminder::class, fn (SendReminder $job) => str_contains($job->text, $needle))->count();
}

it('reminds about a prayer before it starts, once', function () {
    runAt('2026-11-04 15:00');
    runAt('2026-11-04 15:01');
    runAt('2026-11-04 15:03');

    expect(sentTextsContaining('Asr vaqti 15:10'))->toBe(1);
    Queue::assertPushed(SendReminder::class, fn ($job) => $job->chatId === 5005);
});

it('respects prayer settings and inactive prayers', function () {
    Habit::withoutGlobalScope('owner')->where('key', 'asr')->update(['is_active' => false]);
    runAt('2026-11-04 15:00');
    expect(sentTextsContaining('Asr'))->toBe(0);

    $this->user->update(['prayer_reminders' => false]);
    runAt('2026-11-04 16:48');
    expect(sentTextsContaining('Shom'))->toBe(0);
});

it('does not send a reminder that is too late', function () {
    runAt('2026-11-04 15:06'); // asr eslatmasi 15:00 da edi, 5 daqiqalik oyna o'tdi

    expect(sentTextsContaining('Asr'))->toBe(0);
});

it('reminds about a timed task', function () {
    Task::factory()->for($this->user)->create(['title' => 'Arxitektura darsi', 'date' => '2026-11-04', 'start_time' => '19:00']);
    Task::factory()->for($this->user)->done()->create(['title' => 'Bajarilgan', 'date' => '2026-11-04', 'start_time' => '19:00']);

    runAt('2026-11-04 18:45');

    expect(sentTextsContaining('<b>Arxitektura darsi</b> 19:00 da'))->toBe(1)
        ->and(sentTextsContaining('Bajarilgan'))->toBe(0);
});

it('reminds to plan tomorrow in the evening and once more after 30 minutes', function () {
    Task::factory()->for($this->user)->create(['date' => '2026-11-04']); // bugundan qolgan

    runAt('2026-11-04 22:00');
    expect(sentTextsContaining('Ertangi kunni rejalashtirish vaqti'))->toBe(1)
        ->and(sentTextsContaining('1 ta bajarilmagan'))->toBe(1);
    Queue::assertPushed(SendReminder::class, fn ($job) => ($job->replyMarkup['inline_keyboard'][0][0]['web_app']['url'] ?? '') === 'https://enotebook.test/?date=2026-11-05');

    runAt('2026-11-04 22:30');
    expect(sentTextsContaining('hali rejalashtirmadingiz'))->toBe(1);

    runAt('2026-11-04 22:31');
    expect(sentTextsContaining('hali rejalashtirmadingiz'))->toBe(1);
});

it('skips the second planning reminder when the plan exists', function () {
    runAt('2026-11-04 22:00');
    Task::factory()->for($this->user)->create(['date' => '2026-11-05']);
    runAt('2026-11-04 22:30');

    expect(sentTextsContaining('hali rejalashtirmadingiz'))->toBe(0);
});

it('reminds to plan today in morning mode', function () {
    $this->user->update(['planning_mode' => 'morning', 'planning_time' => '08:00']);

    runAt('2026-11-04 08:02');

    expect(sentTextsContaining('Bugungi kunni rejalashtirish'))->toBe(1);
});

it('sends the weekly plan reminder on the chosen day', function () {
    runAt('2026-11-04 20:00'); // chorshanba
    expect(sentTextsContaining('Haftalik reja'))->toBe(0);

    runAt('2026-11-08 20:00'); // yakshanba
    expect(sentTextsContaining('Haftalik reja'))->toBe(1);
    Queue::assertPushed(SendReminder::class, fn ($job) => str_contains($job->replyMarkup['inline_keyboard'][0][0]['web_app']['url'] ?? '', 'view=week&date=2026-11-09'));
});

it('skips users who have not finished onboarding', function () {
    $this->user->update(['onboarding_step' => 'location']);

    runAt('2026-11-04 15:00');

    Queue::assertNothingPushed();
});

it('sends the message through Telegram', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    (new SendReminder(5005, 'Salom', null))->handle(app(TelegramClient::class));

    Http::assertSent(fn ($r) => $r['chat_id'] === 5005 && $r['text'] === 'Salom');
});

it('saves reminder settings', function () {
    $this->actingAs($this->user)->put('/settings', [
        'planning_mode' => 'morning', 'planning_time' => '07:30',
        'weekly_planning_day' => null, 'weekly_planning_time' => '20:00',
        'prayer_reminders' => true, 'prayer_remind_before' => 15,
        'task_reminders' => false, 'task_remind_before' => 15,
        'day_summary' => false,
        'region_id' => Region::firstWhere('name', 'Buxoro viloyati')->id,
    ])->assertSessionHas('success');

    $u = $this->user->fresh();
    expect($u->planning_mode)->toBe('morning')
        ->and($u->planning_time)->toBe('07:30')
        ->and($u->weekly_planning_day)->toBeNull()
        ->and($u->task_reminders)->toBeFalse()
        ->and($u->region->name)->toBe('Buxoro viloyati');

    $this->put('/settings', ['planning_mode' => 'night'])->assertSessionHasErrors(['planning_mode', 'planning_time']);
});
