<?php

namespace App\Console\Commands;

use App\Modules\Telegram\BotAdmin;
use Illuminate\Console\Command;
use RuntimeException;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook';

    protected $description = 'Bot webhook\'ini APP_URL/telegram/webhook ga o\'rnatadi (secret token bilan)';

    public function handle(BotAdmin $bot): int
    {
        try {
            $bot->setWebhook();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Webhook o\'rnatildi: '.$bot->webhookUrl());

        return self::SUCCESS;
    }
}
