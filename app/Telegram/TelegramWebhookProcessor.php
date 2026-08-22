<?php

namespace App\Telegram;

use App\Telegram\Data\IncomingTelegramMessage;
use App\Telegram\Handlers\StartHandler;

class TelegramWebhookProcessor
{
    public function __construct(private readonly StartHandler $startHandler) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        $message = IncomingTelegramMessage::tryFromWebhook($payload);

        if ($message === null || ! $message->isStartCommand()) {
            return;
        }

        $this->startHandler->handle($message);
    }
}
