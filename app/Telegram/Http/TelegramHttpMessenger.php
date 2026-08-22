<?php

namespace App\Telegram\Http;

use App\Telegram\Contracts\TelegramMessenger;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramHttpMessenger implements TelegramMessenger
{
    public function sendMessage(int $chatId, string $text): void
    {
        $token = config('telegram.bot_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        $response = Http::baseUrl(rtrim((string) config('telegram.api_base_url'), '/'))
            ->asJson()
            ->post("/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Telegram sendMessage failed: '.$response->body());
        }
    }
}
