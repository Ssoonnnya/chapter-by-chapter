<?php

namespace App\Telegram;

use App\Telegram\Data\IncomingTelegramCallback;
use App\Telegram\Data\IncomingTelegramMessage;
use App\Telegram\Handlers\AddBookCallbackHandler;
use App\Telegram\Handlers\LibraryCallbackHandler;
use App\Telegram\Handlers\LibraryHandler;
use App\Telegram\Handlers\ReadCallbackHandler;
use App\Telegram\Handlers\ReadHandler;
use App\Telegram\Handlers\SearchHandler;
use App\Telegram\Handlers\StartHandler;
use App\Telegram\Handlers\StopPageHandler;

class TelegramWebhookProcessor
{
    public function __construct(
        private readonly StartHandler $startHandler,
        private readonly SearchHandler $searchHandler,
        private readonly LibraryHandler $libraryHandler,
        private readonly ReadHandler $readHandler,
        private readonly StopPageHandler $stopPageHandler,
        private readonly AddBookCallbackHandler $addBookCallbackHandler,
        private readonly LibraryCallbackHandler $libraryCallbackHandler,
        private readonly ReadCallbackHandler $readCallbackHandler,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        $callback = IncomingTelegramCallback::tryFromWebhook($payload);

        if ($callback !== null) {
            if ($callback->addVolumeId() !== null) {
                $this->addBookCallbackHandler->handle($callback);

                return;
            }

            if ($callback->readAction() !== null) {
                $this->readCallbackHandler->handle($callback);

                return;
            }

            $this->libraryCallbackHandler->handle($callback);

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

            return;
        }

        if ($message->isReadCommand()) {
            $this->readHandler->handle($message);

            return;
        }

        $this->stopPageHandler->handle($message);
    }
}
