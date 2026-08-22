<?php

namespace App\Telegram\Handlers;

use App\Domain\Books\Actions\UpdateLibraryItemStatus;
use App\Domain\Books\Enums\LibraryStatus;
use App\Domain\Books\Exceptions\LibraryItemNotFound;
use App\Domain\Identity\Actions\ResolveUserByTelegramId;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Data\IncomingTelegramCallback;

class LibraryCallbackHandler
{
    public function __construct(
        private readonly ResolveUserByTelegramId $resolveUserByTelegramId,
        private readonly UpdateLibraryItemStatus $updateLibraryItemStatus,
        private readonly LibraryHandler $libraryHandler,
        private readonly TelegramMessenger $messenger,
    ) {}

    public function handle(IncomingTelegramCallback $callback): void
    {
        $user = $this->resolveUserByTelegramId->handle($callback->from->telegramId);

        if ($user === null) {
            $this->messenger->answerCallbackQuery($callback->id, BotReplies::guest());
            $this->messenger->sendMessage($callback->chatId, BotReplies::guest());

            return;
        }

        $filter = $callback->libraryFilter();

        if ($filter !== null) {
            $status = $filter === 'all' ? null : LibraryStatus::tryFrom($filter);
            $this->messenger->answerCallbackQuery($callback->id, 'Library');
            $this->libraryHandler->show($user, $callback->chatId, $status);

            return;
        }

        $change = $callback->libraryStatusChange();

        if ($change === null) {
            return;
        }

        $status = LibraryStatus::from($change['status']);

        try {
            $item = $this->updateLibraryItemStatus->handle($user, $change['id'], $status);
        } catch (LibraryItemNotFound) {
            $this->messenger->answerCallbackQuery($callback->id, BotReplies::libraryItemMissing());
            $this->messenger->sendMessage($callback->chatId, BotReplies::libraryItemMissing());

            return;
        }

        $text = BotReplies::statusUpdated($item->book->title, $status->label());
        $this->messenger->answerCallbackQuery($callback->id, $text);
        $this->messenger->sendMessage($callback->chatId, $text);
        $this->libraryHandler->show($user, $callback->chatId, null);
    }
}
