<?php

namespace App\Modules\Telegram;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Telegram Bot API uchun yupqa mijoz. Token faqat config('telegram.bot_token') dan.
 */
class TelegramClient
{
    public function __construct(private readonly string $token) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function call(string $method, array $params = []): Response
    {
        if ($this->token === '') {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN sozlanmagan.');
        }

        return Http::asJson()
            ->timeout(10)
            ->post("https://api.telegram.org/bot{$this->token}/{$method}", $params);
    }

    /**
     * @param  array<string, mixed>|null  $replyMarkup
     */
    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): Response
    {
        return $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'reply_markup' => $replyMarkup,
        ], fn ($v) => $v !== null));
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): Response
    {
        return $this->call('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ], fn ($v) => $v !== null));
    }
}
