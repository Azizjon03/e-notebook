<?php

namespace App\Modules\Onboarding;

use App\Models\User;
use App\Modules\Core\I18n\Locale;
use App\Modules\Geo\Region;
use App\Modules\Routine\HabitDefaults;
use App\Modules\Telegram\BotTexts;
use App\Modules\Telegram\Summaries;
use App\Modules\Telegram\TelegramClient;
use Carbon\CarbonImmutable;

/**
 * /start dan boshlanadigan ro'yxatdan o'tish oqimi.
 * Holat users.onboarding_step da: name -> location -> gender -> done.
 */
class OnboardingFlow
{
    public const PICK_REGION = 'Viloyatni tanlash';

    public function __construct(
        private readonly TelegramClient $telegram,
        private readonly BotTexts $texts,
        private readonly Summaries $summaries,
    ) {}

    /**
     * @param  array<string, mixed>  $update
     */
    public function handle(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->handleCallback($update['callback_query']);

            return;
        }

        $message = $update['message'] ?? null;
        if (! is_array($message) || ($message['chat']['type'] ?? null) !== 'private') {
            return;
        }

        $from = $message['from'];
        $chatId = (int) $message['chat']['id'];
        $text = trim((string) ($message['text'] ?? ''));
        $user = User::firstWhere('telegram_id', $from['id']);
        app()->setLocale($user?->locale ?? Locale::fromTelegram($from['language_code'] ?? null));

        if (str_starts_with($text, '/start')) {
            $this->start($chatId, $from, $user);

            return;
        }

        if (! $user) {
            $this->telegram->sendMessage($chatId, __('Boshlash uchun /start bosing.'));

            return;
        }

        if (in_array(strtolower(strtok($text, ' @') ?: ''), ['/til', '/lang', '/language'], true)) {
            $this->askLanguage($chatId);

            return;
        }

        match ($user->onboarding_step) {
            'name' => $this->saveName($chatId, $user, $text),
            'location' => $this->saveLocation($chatId, $user, $message),
            'gender' => $this->askGender($chatId),
            default => $this->command($chatId, $user, $text),
        };
    }

    /**
     * @param  array<string, mixed>  $from
     */
    private function start(int $chatId, array $from, ?User $user): void
    {
        if ($user?->hasFinishedOnboarding()) {
            $this->sendAppButton($chatId, $user);

            return;
        }

        $user ??= User::create([
            'telegram_id' => $from['id'],
            'telegram_username' => $from['username'] ?? null,
            'language_code' => $from['language_code'] ?? null,
            'first_name' => $from['first_name'] ?? __('Foydalanuvchi'),
            'locale' => app()->getLocale(),
            'last_name' => $from['last_name'] ?? null,
            'onboarding_step' => 'name',
        ]);
        $user->update(['onboarding_step' => 'name']);

        $this->telegram->sendMessage($chatId, $this->texts->get('welcome'), ['remove_keyboard' => true]);
    }

    private function saveName(int $chatId, User $user, string $text): void
    {
        $parts = preg_split('/\s+/u', $text, 2, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) < 2 || str_starts_with($text, '/') || mb_strlen($text) > 80) {
            $this->telegram->sendMessage($chatId, $this->texts->get('name_invalid'));

            return;
        }

        $user->update([
            'first_name' => mb_convert_case($parts[0], MB_CASE_TITLE),
            'last_name' => mb_convert_case($parts[1], MB_CASE_TITLE),
            'onboarding_step' => 'location',
        ]);

        $this->askLocation($chatId, $user);
    }

    private function askLocation(int $chatId, User $user): void
    {
        $this->telegram->sendMessage($chatId,
            $this->texts->get('ask_location', ['name' => $user->first_name]),
            [
                'keyboard' => [
                    [['text' => __('Joylashuvni yuborish'), 'request_location' => true]],
                    [['text' => __(self::PICK_REGION)]],
                ],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function saveLocation(int $chatId, User $user, array $message): void
    {
        if (isset($message['location'])) {
            $lat = (float) $message['location']['latitude'];
            $lng = (float) $message['location']['longitude'];
            $region = Region::nearestTo($lat, $lng);

            $user->update(['latitude' => $lat, 'longitude' => $lng]);
            $this->setRegion($chatId, $user, $region);

            return;
        }

        if (in_array($message['text'] ?? null, [self::PICK_REGION, __(self::PICK_REGION)], true)) {
            $this->sendRegionList($chatId);

            return;
        }

        $this->askLocation($chatId, $user);
    }

    private function sendRegionList(int $chatId): void
    {
        $buttons = Region::whereNull('parent_id')->orderBy('sort')->get()
            ->map(fn (Region $r) => ['text' => $r->name, 'callback_data' => 'region:'.$r->id])
            ->chunk(2)
            ->map(fn ($row) => $row->values()->all())
            ->values()
            ->all();

        $this->telegram->sendMessage($chatId, __('Viloyatingizni tanlang:'), ['inline_keyboard' => $buttons]);
    }

    private function setRegion(int $chatId, User $user, ?Region $region): void
    {
        if (! $region) {
            $this->sendRegionList($chatId);

            return;
        }

        $user->update([
            'region_id' => $region->id,
            'timezone' => $region->timezone,
            'onboarding_step' => 'gender',
        ]);

        $this->telegram->sendMessage($chatId, __('Mintaqa: <b>:region</b>.', ['region' => $region->name]), ['remove_keyboard' => true]);
        $this->askGender($chatId);
    }

    private function askGender(int $chatId): void
    {
        $this->telegram->sendMessage($chatId,
            $this->texts->get('ask_gender'),
            ['inline_keyboard' => [[
                ['text' => __('Erkak'), 'callback_data' => 'gender:male'],
                ['text' => __('Ayol'), 'callback_data' => 'gender:female'],
            ]]],
        );
    }

    /**
     * @param  array<string, mixed>  $callback
     */
    private function handleCallback(array $callback): void
    {
        $this->telegram->answerCallbackQuery((string) $callback['id']);

        $user = User::firstWhere('telegram_id', $callback['from']['id'] ?? 0);
        $chatId = (int) ($callback['message']['chat']['id'] ?? 0);
        if (! $user || $chatId === 0) {
            return;
        }
        app()->setLocale(Locale::valid($user->locale));

        [$kind, $value] = array_pad(explode(':', (string) ($callback['data'] ?? ''), 2), 2, null);

        if ($kind === 'lang' && array_key_exists((string) $value, Locale::ALL)) {
            $user->update(['locale' => $value]);
            app()->setLocale($value);
            $this->telegram->sendMessage($chatId, __('Til o\'zgartirildi: :language', ['language' => Locale::ALL[$value]]));
            $this->continueOnboarding($chatId, $user);

            return;
        }

        if ($kind === 'region' && $user->onboarding_step === 'location') {
            $this->setRegion($chatId, $user, Region::whereNull('parent_id')->find((int) $value));

            return;
        }

        if ($kind === 'gender' && $user->onboarding_step === 'gender' && in_array($value, ['male', 'female'], true)) {
            $user->update(['gender' => $value, 'onboarding_step' => User::ONBOARDING_DONE]);
            HabitDefaults::ensure($user);
            $this->sendAppButton($chatId, $user, welcome: true);
        }
    }

    /**
     * Ro'yxatdan o'tgan foydalanuvchi buyruqlari: /bugun, /hafta.
     */
    private function command(int $chatId, User $user, string $text): void
    {
        $now = CarbonImmutable::now($user->timezone);
        $command = strtolower(strtok($text, ' @') ?: '');

        match ($command) {
            '/bugun' => $this->telegram->sendMessage($chatId, $this->summaries->today($user, $now), $this->appMarkup(__('Bugungi reja'), '/')),
            '/ertaga' => $this->telegram->sendMessage($chatId, $this->summaries->today($user, $now->addDay()), $this->appMarkup(__('Ertangi reja'), '/?date='.$now->addDay()->toDateString())),
            '/hafta' => $this->telegram->sendMessage($chatId, $this->summaries->week($user, $now), $this->appMarkup(__('Kalendar'), '/calendar?view=week')),
            default => $this->sendAppButton($chatId, $user),
        };
    }

    private function askLanguage(int $chatId): void
    {
        $this->telegram->sendMessage($chatId, __('Tilni tanlang:'), ['inline_keyboard' => [
            collect(Locale::ALL)->map(fn ($name, $code) => ['text' => $name, 'callback_data' => 'lang:'.$code])->values()->all(),
        ]]);
    }

    /**
     * Til o'zgargandan keyin ro'yxatdan o'tishning joriy savolini yangi tilda qayta beradi.
     */
    private function continueOnboarding(int $chatId, User $user): void
    {
        match ($user->onboarding_step) {
            'name' => $this->telegram->sendMessage($chatId, $this->texts->get('welcome')),
            'location' => $this->askLocation($chatId, $user),
            'gender' => $this->askGender($chatId),
            default => $this->sendAppButton($chatId, $user),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function appMarkup(string $label, string $path): array
    {
        return ['inline_keyboard' => [[
            ['text' => $label, 'web_app' => ['url' => rtrim((string) config('app.url'), '/').$path]],
        ]]];
    }

    private function sendAppButton(int $chatId, User $user, bool $welcome = false): void
    {
        $text = $welcome
            ? $this->texts->get('done', ['name' => $user->first_name])
            : $this->texts->get('open_app');

        $this->telegram->sendMessage($chatId, $text, [
            'inline_keyboard' => [[
                ['text' => $this->texts->get('app_button'), 'web_app' => ['url' => rtrim((string) config('app.url'), '/').'/login']],
            ]],
        ]);
    }
}
