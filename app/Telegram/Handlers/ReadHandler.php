<?php

namespace App\Telegram\Handlers;

use App\Domain\Books\Actions\ListLibrary;
use App\Domain\Books\Enums\LibraryStatus;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Domain\Identity\Actions\ResolveUserByTelegramId;
use App\Models\User;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Data\IncomingTelegramMessage;

class ReadHandler
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

        $this->show($user, $message->chatId);
    }

    public function show(User $user, int $chatId): void
    {
        $open = ReadingSession::openFor($user);
        $items = $this->listLibrary->handle($user, LibraryStatus::Reading);

        if ($open === null && $items->isEmpty()) {
            $this->messenger->sendMessage($chatId, BotReplies::readingEmpty());

            return;
        }

        $lines = [];
        $rows = [];

        if ($open !== null) {
            $item = $open->libraryItem;
            $lines[] = BotReplies::nowReading(
                $item->book->title,
                $open->isPaused() ? 'paused' : $this->formatElapsed($open->elapsedSeconds()),
                $open->start_page,
            );
            $rows[] = [
                $open->isPaused()
                    ? ['text' => 'Resume', 'callback_data' => 'read:resume:'.$item->id]
                    : ['text' => 'Pause', 'callback_data' => 'read:pause:'.$item->id],
                ['text' => 'Stop', 'callback_data' => 'read:stop:'.$item->id],
                ['text' => 'Cancel', 'callback_data' => 'read:cancel:'.$item->id],
            ];
        }

        foreach ($items as $item) {
            if ($open !== null && $open->library_item_id === $item->id) {
                continue;
            }

            $lines[] = $this->line($item);
            $rows[] = [[
                'text' => 'Start: '.$item->book->title,
                'callback_data' => 'read:start:'.$item->id,
            ]];
        }

        $this->messenger->sendMessage(
            $chatId,
            implode("\n", $lines),
            ['inline_keyboard' => $rows],
        );
    }

    private function line(LibraryItem $item): string
    {
        $progress = $item->book->page_count === null
            ? (string) $item->current_page
            : $item->current_page.' / '.$item->book->page_count;

        return $item->book->title.' ('.$progress.')';
    }

    private function formatElapsed(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);

        return $minutes.' min';
    }
}
