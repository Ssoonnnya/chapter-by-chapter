<?php

namespace App\Telegram\Handlers;

use App\Domain\Books\Actions\ListLibrary;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Identity\Actions\ResolveUserByTelegramId;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Data\IncomingTelegramMessage;

class LibraryHandler
{
    public function __construct(
        private readonly ResolveUserByTelegramId $resolveUserByTelegramId,
        private readonly ListLibrary $listLibrary,
        private readonly TelegramMessenger $messenger,
    ) {}

    public function handle(IncomingTelegramMessage $message): void
    {
        $user = $this->resolveUserByTelegramId->handle($message->from->telegramId);

        if ($user === null) {
            $this->messenger->sendMessage($message->chatId, BotReplies::guest());

            return;
        }

        $items = $this->listLibrary->handle($user);

        if ($items->isEmpty()) {
            $this->messenger->sendMessage($message->chatId, BotReplies::libraryEmpty());

            return;
        }

        $lines = $items->values()->map(
            fn (LibraryItem $item, mixed $index): string => (((int) $index) + 1).'. '.$item->book->title,
        );

        $this->messenger->sendMessage($message->chatId, $lines->implode("\n"));
    }
}
