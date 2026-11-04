<?php

namespace App\Modules\Core\Reminders;

use App\Modules\Telegram\TelegramClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;

class SendReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>|null  $replyMarkup
     */
    public function __construct(
        public readonly int $chatId,
        public readonly string $text,
        public readonly ?array $replyMarkup = null,
    ) {}

    /**
     * Telegram sekundiga ~30 xabar qabul qiladi.
     *
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new RateLimited('telegram')];
    }

    public function handle(TelegramClient $telegram): void
    {
        $response = $telegram->sendMessage($this->chatId, $this->text, $this->replyMarkup);

        // 403: foydalanuvchi botni bloklagan, qayta urinish foydasiz.
        if ($response->status() === 429) {
            $this->release((int) ($response->json('parameters.retry_after') ?? 5));
        }
    }
}
