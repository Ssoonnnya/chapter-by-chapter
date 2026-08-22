<?php

namespace App\Telegram\Handlers;

use App\Domain\Books\Actions\StartReadingSession;
use App\Domain\Books\Actions\StopReadingSession;
use App\Domain\Books\Exceptions\BookNotReading;
use App\Domain\Books\Exceptions\InvalidLibraryPage;
use App\Domain\Books\Exceptions\LibraryItemNotFound;
use App\Domain\Books\Exceptions\NoOpenSession;
use App\Domain\Books\Exceptions\SessionAlreadyOpen;
use App\Domain\Identity\Actions\ResolveUserByTelegramId;
use App\Models\User;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Data\IncomingTelegramMessage;
use App\Telegram\PendingSessionPage;

class StopPageHandler
{
    public function __construct(
        private readonly ResolveUserByTelegramId $resolveUserByTelegramId,
        private readonly StartReadingSession $startReadingSession,
        private readonly StopReadingSession $stopReadingSession,
        private readonly PendingSessionPage $pendingSessionPage,
        private readonly ReadHandler $readHandler,
        private readonly TelegramMessenger $messenger,
    ) {}

    public function handle(IncomingTelegramMessage $message): bool
    {
        $pending = $this->pendingSessionPage->peek($message->from->telegramId);

        if ($pending === null) {
            return false;
        }

        $user = $this->resolveUserByTelegramId->handle($message->from->telegramId);

        if ($user === null) {
            $this->pendingSessionPage->forget($message->from->telegramId);
            $this->messenger->sendMessage($message->chatId, BotReplies::guest());

            return true;
        }

        $page = $message->barePageNumber();

        if ($page === null) {
            $this->messenger->sendMessage($message->chatId, BotReplies::sendPageNumber());

            return true;
        }

        if ($pending['action'] === 'start') {
            return $this->start($message, $user, $pending['library_item_id'], $page);
        }

        return $this->stop($message, $user, $pending['library_item_id'], $page);
    }

    private function start(IncomingTelegramMessage $message, User $user, int $libraryItemId, int $page): bool
    {
        try {
            $session = $this->startReadingSession->handle($user, $libraryItemId, $page);
        } catch (LibraryItemNotFound) {
            $this->pendingSessionPage->forget($message->from->telegramId);
            $this->messenger->sendMessage($message->chatId, BotReplies::libraryItemMissing());

            return true;
        } catch (BookNotReading) {
            $this->pendingSessionPage->forget($message->from->telegramId);
            $this->messenger->sendMessage($message->chatId, BotReplies::bookNotReading());

            return true;
        } catch (SessionAlreadyOpen $exception) {
            $this->pendingSessionPage->forget($message->from->telegramId);
            $this->messenger->sendMessage($message->chatId, $exception->getMessage());

            return true;
        } catch (InvalidLibraryPage) {
            $this->messenger->sendMessage($message->chatId, BotReplies::invalidPage());

            return true;
        }

        $this->pendingSessionPage->forget($message->from->telegramId);
        $this->messenger->sendMessage($message->chatId, BotReplies::sessionStarted($session->libraryItem->book->title));
        $this->readHandler->show($user, $message->chatId);

        return true;
    }

    private function stop(IncomingTelegramMessage $message, User $user, int $libraryItemId, int $page): bool
    {
        try {
            $session = $this->stopReadingSession->handle($user, $page, $libraryItemId);
        } catch (NoOpenSession) {
            $this->pendingSessionPage->forget($message->from->telegramId);
            $this->messenger->sendMessage($message->chatId, BotReplies::noOpenSession());

            return true;
        } catch (InvalidLibraryPage) {
            $this->messenger->sendMessage($message->chatId, BotReplies::invalidPage());

            return true;
        }

        $this->pendingSessionPage->forget($message->from->telegramId);
        $this->messenger->sendMessage(
            $message->chatId,
            BotReplies::sessionStopped($session->libraryItem->book->title, $page),
        );
        $this->readHandler->show($user, $message->chatId);

        return true;
    }
}
