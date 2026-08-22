<?php

namespace App\Telegram\Handlers;

use App\Domain\Books\Actions\SummarizeReading;
use App\Domain\Books\Data\ReadingSummary;
use App\Domain\Identity\Actions\ResolveUserByTelegramId;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Data\IncomingTelegramMessage;

class StatsHandler
{
    public function __construct(
        private readonly ResolveUserByTelegramId $resolveUserByTelegramId,
        private readonly SummarizeReading $summarizeReading,
        private readonly TelegramMessenger $messenger,
    ) {}

    public function handle(IncomingTelegramMessage $message): void
    {
        $user = $this->resolveUserByTelegramId->handle($message->from->telegramId);

        if ($user === null) {
            $this->messenger->sendMessage($message->chatId, BotReplies::guest());

            return;
        }

        $month = now()->format('Y-m');
        $summary = $this->summarizeReading->handle($user, $month);

        $this->messenger->sendMessage(
            $message->chatId,
            $this->reply($summary),
        );
    }

    private function reply(ReadingSummary $summary): string
    {
        $label = now()->format('F Y');

        if ($summary->sessions === 0) {
            return BotReplies::monthStatsEmpty($label);
        }

        return BotReplies::monthStats(
            $label,
            $summary->sessions,
            $summary->pages,
            $summary->minutes(),
            $summary->pagesPerHour,
        );
    }
}
