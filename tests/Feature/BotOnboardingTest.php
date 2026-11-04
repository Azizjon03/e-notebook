<?php

use App\Models\User;
use App\Modules\Geo\Region;
use Database\Seeders\RegionSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'telegram.bot_token' => '123456:TEST-token',
        'telegram.webhook_secret' => 'test-secret',
        'app.url' => 'https://enotebook.test',
    ]);
    $this->seed(RegionSeeder::class);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
});

function tgUpdate(array $payload): array
{
    static $id = 1;

    return ['update_id' => $id++] + $payload;
}

function tgMessage(array $fields): array
{
    return tgUpdate(['message' => array_merge([
        'message_id' => 1,
        'from' => ['id' => 4242, 'first_name' => 'Ali', 'username' => 'ali'],
        'chat' => ['id' => 4242, 'type' => 'private'],
        'date' => time(),
    ], $fields)]);
}

function tgCallback(string $data): array
{
    return tgUpdate(['callback_query' => [
        'id' => 'cb1',
        'from' => ['id' => 4242, 'first_name' => 'Ali'],
        'message' => ['message_id' => 2, 'chat' => ['id' => 4242, 'type' => 'private']],
        'data' => $data,
    ]]);
}

function sendUpdate(array $update)
{
    return test()->postJson('/telegram/webhook', $update, ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret']);
}

function sentTexts(): array
{
    return Http::recorded()
        ->filter(fn ($pair) => str_ends_with($pair[0]->url(), '/sendMessage'))
        ->map(fn ($pair) => $pair[0]['text'])
        ->values()
        ->all();
}

it('rejects updates without the secret token', function () {
    $this->postJson('/telegram/webhook', tgMessage(['text' => '/start']))->assertForbidden();
    $this->postJson('/telegram/webhook', tgMessage(['text' => '/start']), ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])->assertForbidden();

    expect(User::count())->toBe(0);
});

it('runs the whole onboarding with a shared location', function () {
    sendUpdate(tgMessage(['text' => '/start']))->assertOk();
    $user = User::firstWhere('telegram_id', 4242);
    expect($user->onboarding_step)->toBe('name');

    sendUpdate(tgMessage(['text' => 'madina karimova']));
    $user->refresh();
    expect($user->first_name)->toBe('Madina')
        ->and($user->last_name)->toBe('Karimova')
        ->and($user->onboarding_step)->toBe('location');

    // Chust (Namangan viloyati) koordinatasi
    sendUpdate(tgMessage(['location' => ['latitude' => 41.0036, 'longitude' => 71.2378]]));
    $user->refresh();
    expect($user->region->name)->toBe('Namangan viloyati')
        ->and($user->onboarding_step)->toBe('gender');

    sendUpdate(tgCallback('gender:female'));
    $user->refresh();
    expect($user->gender)->toBe('female')
        ->and($user->hasFinishedOnboarding())->toBeTrue();

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage')
        && ($r['reply_markup']['inline_keyboard'][0][0]['web_app']['url'] ?? null) === 'https://enotebook.test/login');
});

it('lets the user pick a region from the list', function () {
    User::factory()->onboarding('location')->create(['telegram_id' => 4242]);

    sendUpdate(tgMessage(['text' => 'Viloyatni tanlash']));
    Http::assertSent(fn (Request $r) => ($r['text'] ?? '') === 'Viloyatingizni tanlang:'
        && count($r['reply_markup']['inline_keyboard']) === 7);

    $samarqand = Region::firstWhere('name', 'Samarqand viloyati');
    sendUpdate(tgCallback('region:'.$samarqand->id));

    $user = User::firstWhere('telegram_id', 4242);
    expect($user->region_id)->toBe($samarqand->id)
        ->and($user->onboarding_step)->toBe('gender');
});

it('asks again when only one name is sent', function () {
    sendUpdate(tgMessage(['text' => '/start']));
    sendUpdate(tgMessage(['text' => 'Ali']));

    expect(User::firstWhere('telegram_id', 4242)->onboarding_step)->toBe('name')
        ->and(last(sentTexts()))->toContain('ism va familiyani');
});

it('ignores a gender button before the location step is done', function () {
    User::factory()->onboarding('location')->create(['telegram_id' => 4242]);

    sendUpdate(tgCallback('gender:male'));

    expect(User::firstWhere('telegram_id', 4242)->gender)->toBeNull();
});

it('gives a finished user the app button on /start', function () {
    User::factory()->create(['telegram_id' => 4242, 'first_name' => 'Aziz']);

    sendUpdate(tgMessage(['text' => '/start']));

    expect(User::firstWhere('telegram_id', 4242)->first_name)->toBe('Aziz')
        ->and(last(sentTexts()))->toContain('Ilovani ochish');
});

it('ignores group chats', function () {
    sendUpdate(tgMessage(['text' => '/start', 'chat' => ['id' => -100, 'type' => 'group']]))->assertOk();

    expect(User::count())->toBe(0);
});
