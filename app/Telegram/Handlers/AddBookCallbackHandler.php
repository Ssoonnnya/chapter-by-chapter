<?php

namespace App\Telegram\Handlers;

use App\Domain\Books\Actions\ImportAndAddBook;
use App\Domain\Books\Exceptions\BookNotFound;
use App\Domain\Books\Exceptions\UnsupportedBookLanguage;
use App\Domain\Identity\Actions\ResolveUserByTelegramId;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Data\IncomingTelegramCallback;

class AddBookCallbackHandler
{
    public function __construct(
        private readonly ResolveUserByTelegramId $resolveUserByTelegramId,
        private readonly ImportAndAddBook $importAndAddBook,
        private readonly TelegramMessenger $messenger,
    ) {}

    public function handle(IncomingTelegramCallback $callback): void
    {
        $user = $this->resolveUserByTelegramId->handle($callback->from->telegramId);

        if ($user === null) {
            $this->reply($callback, BotReplies::guest());

            return;
        }

        $volumeId = $callback->addVolumeId();

        if ($volumeId === null) {
            return;
        }

        try {
            $result = $this->importAndAddBook->handle($user, $volumeId);
        } catch (BookNotFound) {
            $this->reply($callback, BotReplies::bookNotFound());

            return;
        } catch (UnsupportedBookLanguage) {
            $this->reply($callback, BotReplies::unsupportedLanguage());

            return;
        }

        $text = $result->alreadyInLibrary
            ? BotReplies::alreadyInLibrary($result->book->title)
            : BotReplies::added($result->book->title);

        $this->reply($callback, $text);
    }

    private function reply(IncomingTelegramCallback $callback, string $text): void
    {
        $this->messenger->answerCallbackQuery($callback->id, $text);
        $this->messenger->sendMessage($callback->chatId, $text);
    }
}
