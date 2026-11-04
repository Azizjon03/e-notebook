<?php

use App\Models\User;
use App\Modules\Geo\Region;
use App\Modules\Telegram\BotTexts;
use Database\Seeders\RegionSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config([
        'telegram.bot_token' => '123456:TEST-token',
        'telegram.webhook_secret' => 'test-secret',
        'app.url' => 'https://enotebook.test',
    ]);
    $this->seed(RegionSeeder::class);
    Http::fake(['api.telegram.org/*' => function (Request $r) {
        return match (true) {
            str_ends_with($r->url(), '/getMe') => Http::response(['ok' => true, 'result' => ['id' => 1, 'first_name' => 'e-notebook', 'username' => 'enotebook_bot']]),
            str_ends_with($r->url(), '/getWebhookInfo') => Http::response(['ok' => true, 'result' => ['url' => '', 'pending_update_count' => 0]]),
            str_ends_with($r->url(), '/getMyCommands') => Http::response(['ok' => true, 'result' => []]),
            str_ends_with($r->url(), '/setMyCommands') && ($r['commands'][0]['command'] ?? '') === 'fail' => Http::response(['ok' => false, 'description' => 'Bad Request']),
            default => Http::response(['ok' => true, 'result' => true]),
        };
    }]);
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('hides the admin area from regular users and guests', function () {
    $this->get('/admin')->assertRedirect('/login');

    $this->actingAs(User::factory()->create())->get('/admin')->assertNotFound();
    $this->post('/admin/bot/webhook')->assertNotFound();
    Http::assertNothingSent();
});

it('shows user statistics to an admin', function () {
    $tashkent = Region::firstWhere('name', 'Toshkent shahri');
    User::factory()->count(3)->create(['region_id' => $tashkent->id]);
    User::factory()->onboarding('location')->create();

    $this->actingAs($this->admin)->get('/admin')->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Dashboard')
        ->where('stats.users', 5)
        ->where('stats.onboarded', 4)
        ->where('byRegion.0.name', 'Toshkent shahri')
        ->where('byRegion.0.total', 3)
        ->has('latest', 5)
    );
});

it('shows bot status without ever exposing the token', function () {
    $this->actingAs($this->admin)->get('/admin/bot')
        ->assertDontSee('TEST-token')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Bot')
            ->where('status.username', 'enotebook_bot')
            ->where('status.webhook.expected_url', 'https://enotebook.test/telegram/webhook')
        );
});

it('tells the admin when the token is missing', function () {
    config(['telegram.bot_token' => '']);

    $this->actingAs($this->admin)->get('/admin/bot')
        ->assertInertia(fn (Assert $page) => $page->where('status.configured', false));
    Http::assertNothingSent();
});

it('sets the webhook with the secret token', function () {
    $this->actingAs($this->admin)->post('/admin/bot/webhook')->assertSessionHas('success');

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/setWebhook')
        && $r['url'] === 'https://enotebook.test/telegram/webhook'
        && $r['secret_token'] === 'test-secret');
});

it('saves commands and reports Telegram errors', function () {
    $this->actingAs($this->admin)
        ->post('/admin/bot/commands', ['commands' => [['command' => 'bugun', 'description' => 'Bugungi reja']]])
        ->assertSessionHas('success');

    $this->post('/admin/bot/commands', ['commands' => [['command' => 'Bad Cmd', 'description' => 'x']]])
        ->assertSessionHasErrors('commands.0.command');

    $this->post('/admin/bot/commands', ['commands' => [['command' => 'fail', 'description' => 'x']]])
        ->assertSessionHas('error', 'setMyCommands: Bad Request');
});

it('lets the admin change bot messages used in onboarding', function () {
    $this->actingAs($this->admin)->post('/admin/bot/texts', ['texts' => [
        'welcome' => 'Salom talaba! Ismingizni yozing.',
        'done' => 'Zo\'r, {name}!',
    ]])->assertSessionHas('success');

    $texts = app(BotTexts::class);
    expect($texts->get('welcome'))->toBe('Salom talaba! Ismingizni yozing.')
        ->and($texts->get('done', ['name' => '<Ali>']))->toBe('Zo\'r, &lt;Ali&gt;!');

    // Bot haqiqatan yangi matnni yuboradi
    $this->postJson('/telegram/webhook', ['update_id' => 1, 'message' => [
        'message_id' => 1, 'date' => time(), 'text' => '/start',
        'from' => ['id' => 9001, 'first_name' => 'Ali'], 'chat' => ['id' => 9001, 'type' => 'private'],
    ]], ['X-Telegram-Bot-Api-Secret-Token' => 'test-secret']);

    Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/sendMessage') && $r['text'] === 'Salom talaba! Ismingizni yozing.');

    // Bo'sh qoldirilsa standartga qaytadi
    $this->post('/admin/bot/texts', ['texts' => ['welcome' => '']]);
    expect(app(BotTexts::class)->get('welcome'))->toBe(BotTexts::DEFAULTS['welcome']);
});

it('makes a user admin from the command line', function () {
    $user = User::factory()->create(['telegram_id' => 123123]);

    $this->artisan('user:make-admin', ['telegram_id' => 123123])->assertSuccessful();
    expect($user->fresh()->is_admin)->toBeTrue();

    $this->artisan('user:make-admin', ['telegram_id' => 123123, '--revoke' => true])->assertSuccessful();
    expect($user->fresh()->is_admin)->toBeFalse();

    $this->artisan('user:make-admin', ['telegram_id' => 1])->assertFailed();
});
