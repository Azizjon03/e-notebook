<?php

namespace App\Providers;

use App\Modules\Telegram\Auth\InitDataValidator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InitDataValidator::class, fn () => new InitDataValidator(
            (string) config('telegram.bot_token'),
            (int) config('telegram.init_data_ttl'),
        ));
    }

    public function boot(): void
    {
        //
    }
}
