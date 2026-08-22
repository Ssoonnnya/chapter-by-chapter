<?php

namespace App\Telegram\Http;

use App\Telegram\Contracts\TelegramMessenger;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelegramHttpMessenger implements TelegramMessenger
{
    /**
     * @param  array<string, mixed>|null  $replyMarkup
     */
    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void
    {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $this->post('sendMessage', $payload);
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text): void
    {
        $this->post('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function post(string $method, array $payload): void
    {
        $token = config('telegram.bot_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Telegram bot token is not configured.');
        }

        $response = Http::baseUrl(rtrim((string) config('telegram.api_base_url'), '/'))
            ->asJson()
            ->post("/bot{$token}/{$method}", $payload);

        if ($response->failed()) {
            throw new RuntimeException("Telegram {$method} failed: ".$response->body());
        }
    }
}
