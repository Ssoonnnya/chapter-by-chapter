<?php

namespace App\Console\Commands;

use App\Telegram\TelegramWebhookProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class PollTelegramUpdatesCommand extends Command
{
    protected $signature = 'telegram:poll {--once : Fetch a single batch of updates and exit}';

    protected $description = 'Long-poll Telegram updates for local development';

    public function handle(TelegramWebhookProcessor $processor): int
    {
        $token = config('telegram.bot_token');

        if (! is_string($token) || $token === '') {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        $this->info('Polling Telegram updates. Press Ctrl+C to stop.');

        $offset = 0;
        $once = (bool) $this->option('once');

        do {
            try {
                $response = Http::baseUrl(rtrim((string) config('telegram.api_base_url'), '/'))
                    ->timeout(40)
                    ->get("/bot{$token}/getUpdates", [
                        'offset' => $offset,
                        'timeout' => $once ? 0 : 30,
                    ]);

                if ($response->failed()) {
                    $this->error('getUpdates failed: '.$response->body());

                    if ($once) {
                        return self::FAILURE;
                    }

                    sleep(2);

                    continue;
                }

                /** @var list<array<string, mixed>> $updates */
                $updates = $response->json('result') ?? [];

                foreach ($updates as $update) {
                    $offset = ((int) ($update['update_id'] ?? 0)) + 1;
                    $processor->handle($update);
                }
            } catch (Throwable $exception) {
                $this->error($exception->getMessage());

                if ($once) {
                    return self::FAILURE;
                }

                sleep(2);
            }
        } while (! $once);

        return self::SUCCESS;
    }
}
