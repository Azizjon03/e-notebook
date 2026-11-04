<?php

namespace App\Console\Commands;

use App\Modules\Telegram\TelegramClient;
use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook';

    protected $description = 'Bot webhook\'ini APP_URL/telegram/webhook ga o\'rnatadi (secret token bilan)';

    public function handle(TelegramClient $telegram): int
    {
        $secret = (string) config('telegram.webhook_secret');
        if ($secret === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET .env da yo\'q.');

            return self::FAILURE;
        }

        $url = rtrim((string) config('app.url'), '/').'/telegram/webhook';
        $response = $telegram->call('setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => true,
        ]);

        if (! $response->json('ok')) {
            $this->error('Telegram rad etdi: '.$response->json('description'));

            return self::FAILURE;
        }

        $telegram->call('setChatMenuButton', [
            'menu_button' => ['type' => 'web_app', 'text' => 'Ilova', 'web_app' => ['url' => rtrim((string) config('app.url'), '/').'/login']],
        ]);

        $this->info("Webhook o'rnatildi: {$url}");

        return self::SUCCESS;
    }
}
