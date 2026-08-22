<?php

namespace App\Telegram;

use App\Telegram\Data\IncomingTelegramCallback;
use App\Telegram\Data\IncomingTelegramMessage;
use App\Telegram\Handlers\AddBookCallbackHandler;
use App\Telegram\Handlers\LibraryHandler;
use App\Telegram\Handlers\SearchHandler;
use App\Telegram\Handlers\StartHandler;

class TelegramWebhookProcessor
{
    public function __construct(
        private readonly StartHandler $startHandler,
        private readonly SearchHandler $searchHandler,
        private readonly LibraryHandler $libraryHandler,
        private readonly AddBookCallbackHandler $addBookCallbackHandler,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        $callback = IncomingTelegramCallback::tryFromWebhook($payload);

        if ($callback !== null) {
            $this->addBookCallbackHandler->handle($callback);

            return;
        }

        $message = IncomingTelegramMessage::tryFromWebhook($payload);

        if ($message === null) {
            return;
        }

        if ($message->isStartCommand()) {
            $this->startHandler->handle($message);

            return;
        }

        if ($message->isSearchCommand()) {
            $this->searchHandler->handle($message);

            return;
        }

        if ($message->isLibraryCommand()) {
            $this->libraryHandler->handle($message);
        }
    }
}
