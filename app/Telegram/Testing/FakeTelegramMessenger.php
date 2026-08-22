<?php

namespace App\Telegram\Testing;

use App\Telegram\Contracts\TelegramMessenger;

class FakeTelegramMessenger implements TelegramMessenger
{
    /**
     * @var list<array{chat_id: int, text: string}>
     */
    public array $messages = [];

    public function sendMessage(int $chatId, string $text): void
    {
        $this->messages[] = [
            'chat_id' => $chatId,
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
