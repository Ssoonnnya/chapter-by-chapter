<?php

namespace App\Telegram\Testing;

use App\Telegram\Contracts\TelegramMessenger;

class FakeTelegramMessenger implements TelegramMessenger
{
    /**
     * @var list<array{chat_id: int, text: string, reply_markup: array<string, mixed>|null}>
     */
    public array $messages = [];

    /**
     * @var list<array{callback_query_id: string, text: string}>
     */
    public array $callbackAnswers = [];

    /**
     * @param  array<string, mixed>|null  $replyMarkup
     */
    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void
    {
        $this->messages[] = [
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $replyMarkup,
        ];
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text): void
    {
        $this->callbackAnswers[] = [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
        ];
    }

    /**
     * @return list<string>
     */
    public function texts(): array
    {
        return array_column($this->messages, 'text');
    }
}
