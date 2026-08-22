<?php

namespace App\Telegram\Handlers;

use App\Domain\Books\Actions\SearchBooks;
use App\Domain\Books\Data\BookCard;
use App\Domain\Identity\Actions\ResolveUserByTelegramId;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Data\IncomingTelegramMessage;

class SearchHandler
{
    public function __construct(
        private readonly ResolveUserByTelegramId $resolveUserByTelegramId,
        private readonly SearchBooks $searchBooks,
        private readonly TelegramMessenger $messenger,
    ) {}

    public function handle(IncomingTelegramMessage $message): void
    {
        $user = $this->resolveUserByTelegramId->handle($message->from->telegramId);

        if ($user === null) {
            $this->messenger->sendMessage($message->chatId, BotReplies::guest());

            return;
        }

        $query = $message->searchQuery();

        if ($query === null) {
            $this->messenger->sendMessage($message->chatId, BotReplies::searchUsage());

            return;
        }

        $cards = $this->searchBooks->handle($query, SearchBooks::SUGGESTION_LIMIT);

        if ($cards === []) {
            $this->messenger->sendMessage($message->chatId, BotReplies::searchEmpty());

            return;
        }

        $this->messenger->sendMessage(
            $message->chatId,
            $this->formatList($cards),
            $this->keyboard($cards),
        );
    }

    /**
     * @param  list<BookCard>  $cards
     */
    private function formatList(array $cards): string
    {
        $lines = [];

        foreach ($cards as $index => $card) {
            $authors = $card->authors === [] ? 'Unknown author' : implode(', ', $card->authors);
            $language = $card->language ?? 'unknown';
            $lines[] = ($index + 1).'. '.$card->title.' — '.$authors.' ('.$language.')';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<BookCard>  $cards
     * @return array{inline_keyboard: list<list<array{text: string, callback_data: string}>>}
     */
    private function keyboard(array $cards): array
    {
        $rows = [];

        foreach ($cards as $card) {
            $callback = 'add:'.$card->googleVolumeId;

            if (strlen($callback) > 64) {
                continue;
            }

            $label = mb_strlen($card->title) > 40
                ? mb_substr($card->title, 0, 37).'...'
                : $card->title;

            $rows[] = [[
                'text' => 'Add: '.$label,
                'callback_data' => $callback,
            ]];
        }

        return ['inline_keyboard' => $rows];
    }
}
