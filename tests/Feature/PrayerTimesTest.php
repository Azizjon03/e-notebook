<?php

use App\Models\User;
use App\Modules\Geo\Region;
use App\Modules\Routine\PrayerTime;
use Database\Seeders\RegionSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 05:00:00', 'UTC')); // Toshkent 10:00
    $this->seed(RegionSeeder::class);
    config(['telegram.bot_token' => '123456:TEST-token']);
});

/** Aladhan javobining shakli (ikki kun bilan qisqartirilgan). */
function aladhanMonth(): array
{
    $day = fn (string $date, string $fajr) => [
        'timings' => [
            'Fajr' => "{$fajr} (+05)", 'Sunrise' => '07:05 (+05)', 'Dhuhr' => '12:05 (+05)',
            'Asr' => '15:10 (+05)', 'Sunset' => '16:58 (+05)', 'Maghrib' => '16:58 (+05)', 'Isha' => '18:20 (+05)',
        ],
        'date' => ['gregorian' => ['date' => $date]],
    ];

    return ['code' => 200, 'status' => 'OK', 'data' => [$day('04-11-2026', '05:41'), $day('05-11-2026', '05:42')]];
}

it('fetches a month of prayer times for every region with Hanafi asr', function () {
    Http::fake(['api.aladhan.com/*' => Http::response(aladhanMonth())]);

    $this->artisan('prayer-times:fetch')->assertSuccessful();

    expect(PrayerTime::count())->toBe(14 * 2);
    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/calendar/2026/11') && $r['school'] == 1);

    $tashkent = Region::firstWhere('name', 'Toshkent shahri');
    $row = PrayerTime::where('region_id', $tashkent->id)->whereDate('date', '2026-11-04')->sole();
    expect($row->only('fajr', 'sunrise', 'asr', 'isha'))->toBe(['fajr' => '05:41', 'sunrise' => '07:05', 'asr' => '15:10', 'isha' => '18:20']);

    // Qayta yuklash dublikat yaratmaydi
    $this->artisan('prayer-times:fetch')->assertSuccessful();
    expect(PrayerTime::count())->toBe(28);
});

it('loads next month with --next', function () {
    Http::fake(['api.aladhan.com/*' => Http::response(aladhanMonth())]);

    $this->artisan('prayer-times:fetch --next')->assertSuccessful();

    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/calendar/2026/12'));
});

it('keeps old data and warns admins when the source fails', function () {
    $region = Region::first();
    PrayerTime::create(['region_id' => $region->id, 'date' => '2026-11-04', 'fajr' => '05:40', 'sunrise' => '07:00', 'dhuhr' => '12:00', 'asr' => '15:00', 'maghrib' => '17:00', 'isha' => '18:20']);
    User::factory()->create(['is_admin' => true, 'telegram_id' => 777]);
    Http::fake([
        'api.aladhan.com/*' => Http::response('down', 503),
        'api.telegram.org/*' => Http::response(['ok' => true]),
    ]);

    $this->artisan('prayer-times:fetch')->assertFailed();

    expect(PrayerTime::count())->toBe(1);
    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'api.telegram.org') && $r['chat_id'] === 777
        && str_contains($r['text'], "Namoz vaqtlarini yuklab bo'lmadi"));
});

it('shows the user\'s regional prayer times on the Today page', function () {
    $namangan = Region::firstWhere('name', 'Namangan viloyati');
    $tashkent = Region::firstWhere('name', 'Toshkent shahri');
    $times = ['fajr' => '05:38', 'sunrise' => '07:02', 'dhuhr' => '12:01', 'asr' => '15:05', 'maghrib' => '16:54', 'isha' => '18:16'];
    PrayerTime::create(['region_id' => $namangan->id, 'date' => '2026-11-04'] + $times);
    PrayerTime::create(['region_id' => $tashkent->id, 'date' => '2026-11-04'] + array_map(fn () => '00:00', $times));

    $user = User::factory()->create(['region_id' => $namangan->id]);

    $this->actingAs($user)->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('now', '10:00')
        ->has('prayers', 6)
        ->where('prayers.0', ['key' => 'fajr', 'name' => 'Bomdod', 'time' => '05:38'])
        ->where('prayers.3.name', 'Asr')
    );
});

it('shows no prayer block when the user has no region', function () {
    $this->actingAs(User::factory()->create(['region_id' => null]))
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('prayers', null));
});
