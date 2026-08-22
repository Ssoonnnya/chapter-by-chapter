<?php

namespace App\Telegram\Contracts;

interface TelegramMessenger
{
    public function sendMessage(int $chatId, string $text): void;
}
