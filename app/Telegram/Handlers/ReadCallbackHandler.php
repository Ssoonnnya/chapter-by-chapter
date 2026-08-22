<?php

namespace App\Telegram\Handlers;

use App\Domain\Books\Actions\CancelReadingSession;
use App\Domain\Books\Actions\PauseReadingSession;
use App\Domain\Books\Actions\ResumeReadingSession;
use App\Domain\Books\Exceptions\NoOpenSession;
use App\Domain\Books\Exceptions\SessionAlreadyPaused;
use App\Domain\Books\Exceptions\SessionNotPaused;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Domain\Identity\Actions\ResolveUserByTelegramId;
use App\Models\User;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Data\IncomingTelegramCallback;
use App\Telegram\PendingSessionPage;

class ReadCallbackHandler
{
    public function __construct(
        private readonly ResolveUserByTelegramId $resolveUserByTelegramId,
        private readonly PauseReadingSession $pauseReadingSession,
        private readonly ResumeReadingSession $resumeReadingSession,
        private readonly CancelReadingSession $cancelReadingSession,
        private readonly ReadHandler $readHandler,
        private readonly PendingSessionPage $pendingSessionPage,
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

        $action = $callback->readAction();

        if ($action === null) {
            return;
        }

        match ($action['action']) {
            'start' => $this->askForStartPage($callback, $user, $action['id']),
            'stop' => $this->askForEndPage($callback, $user, $action['id']),
            'pause' => $this->pause($callback, $user, $action['id']),
            'resume' => $this->resume($callback, $user, $action['id']),
            default => $this->cancel($callback, $user, $action['id']),
        };
    }

    private function askForStartPage(IncomingTelegramCallback $callback, User $user, int $libraryItemId): void
    {
        $open = ReadingSession::openFor($user);

        if ($open !== null) {
            $this->reply($callback, BotReplies::sessionAlreadyOpen($open->libraryItem->book->title));

            return;
        }

        $item = LibraryItem::query()
            ->where('user_id', $user->id)
            ->with('book')
            ->find($libraryItemId);

        if ($item === null) {
            $this->reply($callback, BotReplies::libraryItemMissing());

            return;
        }

        $this->pendingSessionPage->remember($callback->from->telegramId, 'start', $libraryItemId);
        $this->reply($callback, BotReplies::askForStartPage($item->book->title));
    }

    private function askForEndPage(IncomingTelegramCallback $callback, User $user, int $libraryItemId): void
    {
        $open = ReadingSession::openFor($user);

        if ($open === null || $open->library_item_id !== $libraryItemId) {
            $this->reply($callback, BotReplies::noOpenSession());

            return;
        }

        $this->pendingSessionPage->remember($callback->from->telegramId, 'stop', $libraryItemId);
        $this->reply($callback, BotReplies::askForPage($open->libraryItem->book->title));
    }

    private function pause(IncomingTelegramCallback $callback, User $user, int $libraryItemId): void
    {
        try {
            $session = $this->pauseReadingSession->handle($user, $libraryItemId);
        } catch (NoOpenSession) {
            $this->reply($callback, BotReplies::noOpenSession());

            return;
        } catch (SessionAlreadyPaused) {
            $this->reply($callback, BotReplies::sessionAlreadyPaused());

            return;
        }

        $this->reply($callback, BotReplies::sessionPaused($session->libraryItem->book->title));
        $this->readHandler->show($user, $callback->chatId);
    }

    private function resume(IncomingTelegramCallback $callback, User $user, int $libraryItemId): void
    {
        try {
            $session = $this->resumeReadingSession->handle($user, $libraryItemId);
        } catch (NoOpenSession) {
            $this->reply($callback, BotReplies::noOpenSession());

            return;
        } catch (SessionNotPaused) {
            $this->reply($callback, BotReplies::sessionNotPaused());

            return;
        }

        $this->reply($callback, BotReplies::sessionResumed($session->libraryItem->book->title));
        $this->readHandler->show($user, $callback->chatId);
    }

    private function cancel(IncomingTelegramCallback $callback, User $user, int $libraryItemId): void
    {
        try {
            $session = $this->cancelReadingSession->handle($user, $libraryItemId);
        } catch (NoOpenSession) {
            $this->reply($callback, BotReplies::noOpenSession());

            return;
        }

        $this->pendingSessionPage->forget($callback->from->telegramId);
        $this->reply($callback, BotReplies::sessionCancelled($session->libraryItem->book->title));
        $this->readHandler->show($user, $callback->chatId);
    }

    private function reply(IncomingTelegramCallback $callback, string $text): void
    {
        $this->messenger->answerCallbackQuery($callback->id, $text);
        $this->messenger->sendMessage($callback->chatId, $text);
    }
}
