<?php

it('keeps the Telegram bot token out of the repository', function () {
    $example = file_get_contents(__DIR__.'/../../.env.example');

    expect($example)->toMatch('/^TELEGRAM_BOT_TOKEN=$/m');
});
