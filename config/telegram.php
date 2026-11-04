<?php

return [
    // Bot tokeni faqat .env dan o'qiladi. Uni kodga yoki repoga yozmang.
    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

    // Mini App initData necha soniyagacha amal qiladi (24 soat).
    'init_data_ttl' => 86400,
];
