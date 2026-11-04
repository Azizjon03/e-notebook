<?php

use App\Models\User;
use App\Modules\Core\Reminders\SendReminder;
use App\Modules\Geo\Region;
use App\Modules\Planner\Task;
use App\Modules\Routine\DailyEntry;
use App\Modules\Routine\Habit;
use App\Modules\Routine\HabitDefaults;
use App\Modules\Routine\PrayerTime;
use App\Modules\Study\StudySession;
use Database\Seeders\RegionSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 10:00', 'Asia/Tashkent'));
    config(['telegram.bot_token' => '123456:TEST-token', 'telegram.webhook_secret' => 'test-secret', 'app.url' => 'https://enotebook.test']);
    $this->seed(RegionSeeder::class);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    $region = Region::firstWhere('name', 'Toshkent shahri');
    $this->user = User::factory()->create(['telegram_id' => 6006, 'region_id' => $region->id]);
    HabitDefaults::ensure($this->user);
    PrayerTime::create(['region_id' => $region->id, 'date' => '2026-11-04', 'fajr' => '05:41', 'sunrise' => '07:05', 'dhuhr' => '12:05', 'asr' => '15:10', 'maghrib' => '16:58', 'isha' => '18:20']);
});

function command(string $text): void
{
    test()->postJson('/telegram/webhook', ['update_id' => random_int(1, 1_000_000), 'message' => [
        'message_id' => 1, 'date' => time(), 'text' => $text,
        'from' => ['id' => 6006, 'first_name' => 'Ali'], 'chat' => ['id' => 6006, 'type' => 'private'],
    ]], ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'])->assertOk();
}

function lastText(): string
{
    return Http::recorded()->last()[0]['text'];
}

it('answers /bugun with today\'s tasks and prayer times', function () {
    Task::factory()->for($this->user)->create(['title' => 'Arxitektura darsi', 'date' => '2026-11-04', 'start_time' => '19:00']);
    Task::factory()->for($this->user)->done()->create(['title' => 'Zal', 'date' => '2026-11-04']);
    Task::factory()->create(['title' => 'Begona vazifa', 'date' => '2026-11-04']);

    command('/bugun');

    expect(lastText())
        ->toContain('Chorshanba, 4-noyabr')
        ->toContain('Vazifalar: 1/2')
        ->toContain('▫️ 19:00 Arxitektura darsi')
        ->toContain('✅ Zal')
        ->toContain('Bomdod 05:41')
        ->not->toContain('Begona');
});

it('answers /ertaga and /hafta', function () {
    Task::factory()->for($this->user)->create(['title' => 'Ertangi ish', 'date' => '2026-11-05']);
    Task::factory()->for($this->user)->done()->create(['date' => '2026-11-01']);
    DailyEntry::create(['habit_id' => Habit::withoutGlobalScope('owner')->where('key', 'fajr')->value('id'), 'date' => '2026-11-03', 'done_at' => now()]);
    StudySession::withoutGlobalScope('owner')->create(['user_id' => $this->user->id, 'area_id' => 1, 'started_at' => now()->subDay(), 'ended_at' => now()->subDay()->addMinutes(50), 'minutes' => 50]);

    command('/ertaga');
    expect(lastText())->toContain('Payshanba, 5-noyabr')->toContain('Ertangi ish');

    command('/hafta');
    expect(lastText())
        ->toContain('Oxirgi 7 kun')
        ->toContain('Vazifalar: 1/1')
        ->toContain('Namoz: 1/35')
        ->toContain("O'qish: 50 daqiqa");
});

it('escapes task titles in HTML messages', function () {
    Task::factory()->for($this->user)->create(['title' => '<b>x</b> & y', 'date' => '2026-11-04']);

    command('/bugun');

    expect(lastText())->toContain('&lt;b&gt;x&lt;/b&gt; &amp; y');
});

it('sends a day summary at 21:30 only when something happened', function () {
    Queue::fake();
    $this->user->update(['planning_mode' => 'off', 'weekly_planning_day' => null, 'prayer_reminders' => false]);

    Carbon::setTestNow(Carbon::parse('2026-11-04 21:30', 'Asia/Tashkent'));
    $this->artisan('reminders:dispatch');
    Queue::assertNothingPushed();

    Carbon::setTestNow(Carbon::parse('2026-11-05 21:31', 'Asia/Tashkent'));
    Task::factory()->for($this->user)->done()->create(['date' => '2026-11-05']);
    Task::factory()->for($this->user)->create(['date' => '2026-11-05']);
    DailyEntry::create(['habit_id' => Habit::withoutGlobalScope('owner')->where('key', 'steps')->value('id'), 'date' => '2026-11-05', 'value' => 7200]);
    $this->artisan('reminders:dispatch');

    Queue::assertPushed(SendReminder::class, fn (SendReminder $job) => str_contains($job->text, 'Kun yakuni')
        && str_contains($job->text, 'Vazifalar: 1/2')
        && str_contains($job->text, 'Qadam: 7 200 / 10 000')
        && str_contains($job->text, '1 ta vazifa qoldi'));
});

it('lets the user turn the day summary off', function () {
    Queue::fake();
    $this->user->update(['day_summary' => false, 'planning_mode' => 'off', 'weekly_planning_day' => null, 'prayer_reminders' => false]);
    Task::factory()->for($this->user)->done()->create(['date' => '2026-11-04']);

    Carbon::setTestNow(Carbon::parse('2026-11-04 21:30', 'Asia/Tashkent'));
    $this->artisan('reminders:dispatch');

    Queue::assertNothingPushed();
});

it('still answers unknown text with the app button', function () {
    command('salom');

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage') && ($r['reply_markup']['inline_keyboard'][0][0]['web_app'] ?? null));
});
