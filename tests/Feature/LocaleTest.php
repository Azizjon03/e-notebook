<?php

use App\Models\User;
use App\Modules\Core\Reminders\SendReminder;
use App\Modules\Geo\Region;
use App\Modules\Routine\HabitDefaults;
use App\Modules\Routine\PrayerTime;
use App\Modules\Telegram\BotTexts;
use Database\Seeders\RegionSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RegionSeeder::class);
    config(['app.url' => 'https://enotebook.test', 'telegram.bot_token' => '123456:TEST-token', 'telegram.webhook_secret' => 'test-secret']);
});

it('shares the language and its dictionary with the pages', function () {
    $user = User::factory()->create(['locale' => 'ru']);

    $this->actingAs($user)->get('/')->assertInertia(fn (Assert $page) => $page
        ->where('locale', 'ru')
        ->where('translations.Bugun', 'Сегодня')
        ->has('locales', 3));

    $user->update(['locale' => 'uz']);
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('locale', 'uz')->where('translations', []));
});

it('switches the language for a user and for a guest', function () {
    $this->post('/locale', ['locale' => 'en'])->assertRedirect();
    $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));

    $user = User::factory()->create();
    $this->actingAs($user)->post('/locale', ['locale' => 'ru']);
    expect($user->fresh()->locale)->toBe('ru');

    $this->post('/locale', ['locale' => 'de'])->assertSessionHasErrors('locale');
});

it('translates server labels: habits, prayer names, validation', function () {
    $user = User::factory()->create(['locale' => 'en', 'region_id' => Region::firstWhere('name', 'Toshkent shahri')->id]);
    HabitDefaults::ensure($user);
    $this->actingAs($user);

    $this->get('/habits')->assertInertia(fn (Assert $page) => $page
        ->where('habits.0.name', 'Fajr')
        ->where('areas.0.name', 'Daily routine'));

    $this->get('/settings')->assertInertia(fn (Assert $page) => $page->where('regions.0.name', 'Tashkent city'));

    $this->post('/tasks', ['title' => '', 'date' => '2026-11-04'])->assertSessionHasErrors(['title' => 'Enter the task name.']);
});

it('keeps custom habit names as written', function () {
    $user = User::factory()->create(['locale' => 'ru']);
    $this->actingAs($user)->post('/habits', ['name' => 'Bugun', 'area' => 'routine', 'kind' => 'check']);

    $this->get('/habits')->assertInertia(fn (Assert $page) => $page->where('habits', fn ($habits) => collect($habits)->contains('name', 'Bugun')));
});

it('sends reminders in the user\'s language', function () {
    Queue::fake();
    $region = Region::firstWhere('name', 'Toshkent shahri');
    $user = User::factory()->create(['telegram_id' => 7007, 'region_id' => $region->id, 'locale' => 'ru', 'planning_mode' => 'off']);
    HabitDefaults::ensure($user);
    PrayerTime::create(['region_id' => $region->id, 'date' => '2026-11-04', 'fajr' => '05:41', 'sunrise' => '07:05', 'dhuhr' => '12:05', 'asr' => '15:10', 'maghrib' => '16:58', 'isha' => '18:20']);

    Carbon::setTestNow(Carbon::parse('2026-11-04 15:00', 'Asia/Tashkent'));
    $this->artisan('reminders:dispatch')->assertSuccessful();

    Queue::assertPushed(SendReminder::class, fn (SendReminder $job) => $job->text === 'Время намаза Аср наступает в 15:10.');
    expect(app()->getLocale())->toBe('uz'); // til qayta tiklanadi
});

it('lets the bot user change the language with /til', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret'];
    $from = ['id' => 5151, 'first_name' => 'Ivan', 'language_code' => 'ru'];
    $chat = ['id' => 5151, 'type' => 'private'];

    // Telegram tili ruscha: ro'yxatdan o'tish ruscha boshlanadi.
    $this->postJson('/telegram/webhook', ['update_id' => 1, 'message' => ['message_id' => 1, 'from' => $from, 'chat' => $chat, 'date' => time(), 'text' => '/start']], $headers);
    $user = User::firstWhere('telegram_id', 5151);
    expect($user->locale)->toBe('ru');

    $this->postJson('/telegram/webhook', ['update_id' => 2, 'message' => ['message_id' => 2, 'from' => $from, 'chat' => $chat, 'date' => time(), 'text' => '/til']], $headers);
    $this->postJson('/telegram/webhook', ['update_id' => 3, 'callback_query' => ['id' => 'c', 'from' => $from, 'message' => ['message_id' => 3, 'chat' => $chat], 'data' => 'lang:en']], $headers);

    expect($user->fresh()->locale)->toBe('en');

    $texts = Http::recorded()->filter(fn ($p) => str_ends_with($p[0]->url(), '/sendMessage'))->map(fn ($p) => $p[0]['text'])->values()->all();
    expect($texts[0])->toStartWith('Ассаляму алейкум')
        ->and($texts)->toContain('Выберите язык:')
        ->and($texts)->toContain('Language changed: English')
        ->and(end($texts))->toStartWith('Assalamu alaykum! Welcome'); // joriy savol yangi tilda qayta beriladi
});

it('lets the admin edit bot texts per language', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin)->post('/admin/bot/texts', ['locale' => 'ru', 'texts' => ['open_app' => 'Жмите кнопку']]);

    $texts = app(BotTexts::class);
    expect($texts->get('open_app', [], 'ru'))->toBe('Жмите кнопку')
        ->and($texts->get('open_app', [], 'uz'))->toBe(BotTexts::DEFAULTS['open_app'])
        ->and($texts->get('open_app', [], 'en'))->toBe('Tap the button to open the app.');

    $this->get('/admin/bot?lang=ru')->assertInertia(fn (Assert $page) => $page
        ->where('textLocale', 'ru')
        ->where('texts.open_app', 'Жмите кнопку')
        ->where('defaults.open_app', 'Нажмите кнопку, чтобы открыть приложение.'));
});
