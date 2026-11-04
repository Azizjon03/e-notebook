<?php

namespace App\Modules\Telegram;

use App\Modules\Core\I18n\Locale;
use RuntimeException;

/**
 * Bot API orqali botni sozlash: webhook, buyruqlar, tavsif, menyu tugmasi.
 */
class BotAdmin
{
    public const DEFAULT_COMMANDS = [
        ['command' => 'start', 'description' => 'Boshlash va ilovani ochish'],
        ['command' => 'bugun', 'description' => 'Bugungi reja'],
        ['command' => 'ertaga', 'description' => 'Ertangi reja'],
        ['command' => 'hafta', 'description' => 'Oxirgi 7 kun natijasi'],
        ['command' => 'til', 'description' => 'Tilni o\'zgartirish'],
    ];

    public function __construct(
        private readonly TelegramClient $telegram,
        private readonly BotTexts $texts,
    ) {}

    public function isConfigured(): bool
    {
        return (string) config('telegram.bot_token') !== '';
    }

    public function appUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/login';
    }

    public function webhookUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/telegram/webhook';
    }

    /**
     * Botning hozirgi holati. Token sozlanmagan yoki Telegram javob bermasa, null maydonlar bilan qaytadi.
     *
     * @return array<string, mixed>
     */
    public function status(): array
    {
        if (! $this->isConfigured()) {
            return ['configured' => false];
        }

        $me = $this->result('getMe');
        $webhook = $this->result('getWebhookInfo');

        return [
            'configured' => true,
            'username' => $me['username'] ?? null,
            'name' => $me['first_name'] ?? null,
            'description' => $this->result('getMyDescription')['description'] ?? '',
            'short_description' => $this->result('getMyShortDescription')['short_description'] ?? '',
            'commands' => $this->result('getMyCommands') ?? [],
            'webhook' => [
                'url' => $webhook['url'] ?? '',
                'expected_url' => $this->webhookUrl(),
                'pending' => $webhook['pending_update_count'] ?? 0,
                'last_error' => $webhook['last_error_message'] ?? null,
                'last_error_at' => isset($webhook['last_error_date']) ? date('Y-m-d H:i', $webhook['last_error_date']) : null,
            ],
        ];
    }

    public function setWebhook(): void
    {
        $secret = (string) config('telegram.webhook_secret');
        if ($secret === '') {
            throw new RuntimeException('TELEGRAM_WEBHOOK_SECRET .env da yo\'q.');
        }

        $this->ensure('setWebhook', [
            'url' => $this->webhookUrl(),
            'secret_token' => $secret,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => true,
        ]);

        $this->setMenuButton();
    }

    public function setMenuButton(): void
    {
        $this->ensure('setChatMenuButton', [
            'menu_button' => ['type' => 'web_app', 'text' => $this->texts->get('menu_button', [], Locale::DEFAULT), 'web_app' => ['url' => $this->appUrl()]],
        ]);
    }

    public function setProfile(?string $name, string $description, string $shortDescription): void
    {
        if ($name !== null && $name !== '') {
            $this->ensure('setMyName', ['name' => $name]);
        }
        $this->ensure('setMyDescription', ['description' => $description]);
        $this->ensure('setMyShortDescription', ['short_description' => $shortDescription]);
    }

    /**
     * @param  list<array{command:string, description:string}>  $commands
     */
    public function setCommands(array $commands): void
    {
        // Standart (o'zbekcha) ro'yxat, keyin rus va ingliz tilidagi foydalanuvchilar uchun tarjimasi.
        $this->ensure('setMyCommands', ['commands' => $commands]);

        foreach (array_keys(Locale::ALL) as $locale) {
            if ($locale === Locale::DEFAULT) {
                continue;
            }
            $this->ensure('setMyCommands', [
                'commands' => array_map(fn ($c) => ['command' => $c['command'], 'description' => __($c['description'], [], $locale)], $commands),
                'language_code' => $locale,
            ]);
        }
    }

    private function result(string $method): mixed
    {
        try {
            $response = $this->telegram->call($method);
        } catch (\Throwable) {
            return null;
        }

        return $response->json('ok') ? $response->json('result') : null;
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function ensure(string $method, array $params): void
    {
        $response = $this->telegram->call($method, $params);

        if (! $response->json('ok')) {
            throw new RuntimeException("{$method}: ".($response->json('description') ?? 'Telegram javob bermadi'));
        }
    }
}
