<?php

namespace App\Modules\Onboarding;

use App\Models\User;
use App\Modules\Geo\Region;
use App\Modules\Telegram\TelegramClient;

/**
 * /start dan boshlanadigan ro'yxatdan o'tish oqimi.
 * Holat users.onboarding_step da: name -> location -> gender -> done.
 */
class OnboardingFlow
{
    public const PICK_REGION = 'Viloyatni tanlash';

    public function __construct(private readonly TelegramClient $telegram) {}

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

        if (str_starts_with($text, '/start')) {
            $this->start($chatId, $from, $user);

            return;
        }

        if (! $user) {
            $this->telegram->sendMessage($chatId, 'Boshlash uchun /start bosing.');

            return;
        }

        match ($user->onboarding_step) {
            'name' => $this->saveName($chatId, $user, $text),
            'location' => $this->saveLocation($chatId, $user, $message),
            'gender' => $this->askGender($chatId),
            default => $this->sendAppButton($chatId, $user),
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
            'first_name' => $from['first_name'] ?? 'Foydalanuvchi',
            'last_name' => $from['last_name'] ?? null,
            'onboarding_step' => 'name',
        ]);
        $user->update(['onboarding_step' => 'name']);

        $this->telegram->sendMessage($chatId,
            "Assalomu alaykum! e-notebook'ga xush kelibsiz.\n\nIsm va familiyangizni yozing, masalan: <i>Ali Valiyev</i>",
            ['remove_keyboard' => true],
        );
    }

    private function saveName(int $chatId, User $user, string $text): void
    {
        $parts = preg_split('/\s+/u', $text, 2, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) < 2 || str_starts_with($text, '/') || mb_strlen($text) > 80) {
            $this->telegram->sendMessage($chatId, 'Iltimos, ism va familiyani bitta xabarda yozing, masalan: <i>Ali Valiyev</i>');

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
            "Rahmat, {$user->first_name}! Namoz vaqtlari to'g'ri chiqishi uchun joylashuvingizni yuboring yoki viloyatni tanlang.",
            [
                'keyboard' => [
                    [['text' => 'Joylashuvni yuborish', 'request_location' => true]],
                    [['text' => self::PICK_REGION]],
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

        if (($message['text'] ?? null) === self::PICK_REGION) {
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

        $this->telegram->sendMessage($chatId, 'Viloyatingizni tanlang:', ['inline_keyboard' => $buttons]);
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

        $this->telegram->sendMessage($chatId, "Mintaqa: <b>{$region->name}</b>.", ['remove_keyboard' => true]);
        $this->askGender($chatId);
    }

    private function askGender(int $chatId): void
    {
        $this->telegram->sendMessage($chatId,
            'Jinsingizni tanlang. Bu faqat sizga mos zal dasturlarini ko\'rsatish uchun kerak.',
            ['inline_keyboard' => [[
                ['text' => 'Erkak', 'callback_data' => 'gender:male'],
                ['text' => 'Ayol', 'callback_data' => 'gender:female'],
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

        [$kind, $value] = array_pad(explode(':', (string) ($callback['data'] ?? ''), 2), 2, null);

        if ($kind === 'region' && $user->onboarding_step === 'location') {
            $this->setRegion($chatId, $user, Region::whereNull('parent_id')->find((int) $value));

            return;
        }

        if ($kind === 'gender' && $user->onboarding_step === 'gender' && in_array($value, ['male', 'female'], true)) {
            $user->update(['gender' => $value, 'onboarding_step' => User::ONBOARDING_DONE]);
            $this->sendAppButton($chatId, $user, welcome: true);
        }
    }

    private function sendAppButton(int $chatId, User $user, bool $welcome = false): void
    {
        $text = $welcome
            ? "Tayyor, {$user->first_name}! Endi kunlik rejalaringizni ilovada tuzishingiz mumkin."
            : 'Ilovani ochish uchun tugmani bosing.';

        $this->telegram->sendMessage($chatId, $text, [
            'inline_keyboard' => [[
                ['text' => 'Ilovani ochish', 'web_app' => ['url' => rtrim((string) config('app.url'), '/').'/login']],
            ]],
        ]);
    }
}
