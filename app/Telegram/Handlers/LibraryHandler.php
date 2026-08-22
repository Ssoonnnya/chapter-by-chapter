<?php

namespace App\Telegram\Handlers;

use App\Domain\Books\Actions\ListLibrary;
use App\Domain\Books\Enums\LibraryStatus;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Identity\Actions\ResolveUserByTelegramId;
use App\Models\User;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Data\IncomingTelegramMessage;
use Illuminate\Support\Collection;

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

        $this->show($user, $message->chatId, $this->statusFromFilter($message->libraryFilter()));
    }

    public function show(User $user, int $chatId, ?LibraryStatus $status): void
    {
        $items = $this->listLibrary->handle($user, $status);

        if ($items->isEmpty()) {
            $this->messenger->sendMessage(
                $chatId,
                BotReplies::libraryEmpty(),
                $this->filterKeyboard($status),
            );

            return;
        }

        $lines = $items->values()->map(
            fn (LibraryItem $item, mixed $index): string => $this->line((int) $index, $item),
        );

        $this->messenger->sendMessage(
            $chatId,
            $lines->implode("\n"),
            $this->keyboard($items, $status),
        );
    }

    private function line(int $index, LibraryItem $item): string
    {
        $progress = $item->book->page_count === null
            ? (string) $item->current_page
            : $item->current_page.' / '.$item->book->page_count;

        return ($index + 1).'. '.$item->book->title.' — '.$item->status->label().' ('.$progress.')';
    }

    /**
     * @param  Collection<int, LibraryItem>  $items
     * @return array{inline_keyboard: list<list<array{text: string, callback_data: string}>>}
     */
    private function keyboard($items, ?LibraryStatus $filter): array
    {
        $rows = $this->filterKeyboard($filter)['inline_keyboard'];

        foreach ($items as $item) {
            $rows[] = array_map(
                fn (LibraryStatus $status): array => [
                    'text' => ($item->status === $status ? '• ' : '').$status->label(),
                    'callback_data' => 'lib:s:'.$item->id.':'.$status->value,
                ],
                LibraryStatus::cases(),
            );
        }

        return ['inline_keyboard' => $rows];
    }

    /**
     * @return array{inline_keyboard: list<list<array{text: string, callback_data: string}>>}
     */
    private function filterKeyboard(?LibraryStatus $current): array
    {
        $buttons = [[
            'text' => $current === null ? '• All' : 'All',
            'callback_data' => 'lib:f:all',
        ]];

        foreach (LibraryStatus::cases() as $status) {
            $buttons[] = [
                'text' => $current === $status ? '• '.$status->label() : $status->label(),
                'callback_data' => 'lib:f:'.$status->value,
            ];
        }

        return ['inline_keyboard' => [$buttons]];
    }

    private function statusFromFilter(?string $filter): ?LibraryStatus
    {
        if ($filter === null || $filter === 'all') {
            return null;
        }

        return LibraryStatus::tryFrom($filter);
    }
}
