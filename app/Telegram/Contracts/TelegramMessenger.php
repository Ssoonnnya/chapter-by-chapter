<?php

namespace App\Telegram\Contracts;

interface TelegramMessenger
{
    /**
     * @param  array<string, mixed>|null  $replyMarkup
     */
    public function sendMessage(int $chatId, string $text, ?array $replyMarkup = null): void;

    public function answerCallbackQuery(string $callbackQueryId, string $text): void;
}
