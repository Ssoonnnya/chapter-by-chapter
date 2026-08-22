<?php

namespace App\Providers;

use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Http\TelegramHttpMessenger;
use App\Telegram\Testing\FakeTelegramMessenger;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    /**
     * Register Telegram bindings.
     */
    public function register(): void
    {
        $this->app->singleton(
            TelegramMessenger::class,
            fn (): TelegramMessenger => $this->app->runningUnitTests()
                ? new FakeTelegramMessenger
                : new TelegramHttpMessenger,
        );
    }
}
