<?php

namespace App\Console\Commands;

use App\Telegram\BotCommands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramCommandsCommand extends Command
{
    protected $signature = 'telegram:set-commands';

    protected $description = 'Register the Telegram bot command menu';

    public function handle(): int
    {
        $token = config('telegram.bot_token');

        if (! is_string($token) || $token === '') {
            $this->error('TELEGRAM_BOT_TOKEN is not set.');

            return self::FAILURE;
        }

        $response = Http::baseUrl(rtrim((string) config('telegram.api_base_url'), '/'))
            ->asJson()
            ->post("/bot{$token}/setMyCommands", [
                'commands' => BotCommands::definitions(),
            ]);

        if ($response->failed() || $response->json('ok') !== true) {
            $this->error('Failed to set commands: '.$response->body());

            return self::FAILURE;
        }

        $this->info('Bot command menu registered.');

        return self::SUCCESS;
    }
}
