<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:set-webhook';

    protected $description = 'Register the Telegram webhook URL and secret token';

    public function handle(): int
    {
        $token = config('telegram.bot_token');
        $url = config('telegram.webhook_url');
        $secret = config('telegram.webhook_secret');

        if (! is_string($token) || $token === '') {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        if (! is_string($url) || $url === '') {
            $this->error('TELEGRAM_WEBHOOK_URL is not set.');

            return self::FAILURE;
        }

        if (! is_string($secret) || $secret === '') {
            $this->error('TELEGRAM_WEBHOOK_SECRET is not set.');

            return self::FAILURE;
        }

        $response = Http::baseUrl(rtrim((string) config('telegram.api_base_url'), '/'))
            ->asJson()
            ->post("/bot{$token}/setWebhook", [
                'url' => $url,
                'secret_token' => $secret,
            ]);

        if ($response->failed() || $response->json('ok') !== true) {
            $this->error('Failed to set webhook: '.$response->body());

            return self::FAILURE;
        }

        $this->info('Webhook set to '.$url);

        return self::SUCCESS;
    }
}
