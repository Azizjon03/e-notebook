<?php

namespace App\Modules\Core\Reminders;

/**
 * Yuborilishi kerak bo'lgan bitta eslatma.
 */
final class Reminder
{
    /**
     * @param  array<string, mixed>|null  $replyMarkup
     */
    public function __construct(
        public readonly string $ref,
        public readonly string $text,
        public readonly ?array $replyMarkup = null,
    ) {}
}
