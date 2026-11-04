<?php

namespace App\Providers;

use App\Modules\Routine\AladhanSource;
use App\Modules\Routine\PrayerTimeSource;
use App\Modules\Telegram\Auth\InitDataValidator;
use App\Modules\Telegram\TelegramClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InitDataValidator::class, fn () => new InitDataValidator(
            (string) config('telegram.bot_token'),
            (int) config('telegram.init_data_ttl'),
        ));

        $this->app->bind(PrayerTimeSource::class, AladhanSource::class);

        $this->app->bind(TelegramClient::class, fn () => new TelegramClient(
            (string) config('telegram.bot_token'),
        ));
    }

    public function boot(): void
    {
        //
    }
}
