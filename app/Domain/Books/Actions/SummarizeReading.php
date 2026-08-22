<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Data\ReadingDay;
use App\Domain\Books\Data\ReadingSummary;
use App\Domain\Books\Models\ReadingSession;
use App\Models\User;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class SummarizeReading
{
    public function handle(User $user, ?string $month = null, ?int $libraryItemId = null): ReadingSummary
    {
        $timezone = (string) config('app.timezone');
        $range = $month === null ? null : $this->monthRange($month, $timezone);

        $sessions = ReadingSession::query()
            ->stopped()
            ->where('user_id', $user->id)
            ->when($libraryItemId !== null, fn ($query) => $query->where('library_item_id', $libraryItemId))
            ->when($range !== null, fn ($query) => $query->whereBetween('ended_at', [$range[0], $range[1]]))
            ->get();

        $totalPages = 0;
        $totalSeconds = 0;
        /** @var array<string, array{sessions: int, pages: int, seconds: int}> $buckets */
        $buckets = [];

        foreach ($sessions as $session) {
            $endedAt = $session->ended_at;

            if ($endedAt === null) {
                continue;
            }

            $pages = $session->pagesRead() ?? 0;
            $seconds = $session->durationSeconds() ?? 0;
            $date = $endedAt->timezone($timezone)->toDateString();

            $totalPages += $pages;
            $totalSeconds += $seconds;

            if (! isset($buckets[$date])) {
                $buckets[$date] = ['sessions' => 0, 'pages' => 0, 'seconds' => 0];
            }

            $buckets[$date]['sessions']++;
            $buckets[$date]['pages'] += $pages;
            $buckets[$date]['seconds'] += $seconds;
        }

        return new ReadingSummary(
            month: $month,
            sessions: $sessions->count(),
            pages: $totalPages,
            seconds: $totalSeconds,
            pagesPerHour: $totalSeconds > 0
                ? round($totalPages / ($totalSeconds / 3600), 1)
                : null,
            days: $range === null ? [] : $this->daysForMonth($range[0], $buckets),
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function monthRange(string $month, string $timezone): array
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            throw new InvalidArgumentException('Month must be Y-m.');
        }

        $start = Carbon::parse($month.'-01 00:00:00', $timezone);

        return [$start->copy()->startOfMonth(), $start->copy()->endOfMonth()];
    }

    /**
     * @param  array<string, array{sessions: int, pages: int, seconds: int}>  $buckets
     * @return list<ReadingDay>
     */
    private function daysForMonth(Carbon $start, array $buckets): array
    {
        $days = [];

        for ($day = 1; $day <= $start->daysInMonth; $day++) {
            $date = $start->copy()->day($day)->toDateString();
            $bucket = $buckets[$date] ?? ['sessions' => 0, 'pages' => 0, 'seconds' => 0];

            $days[] = new ReadingDay(
                date: $date,
                sessions: $bucket['sessions'],
                pages: $bucket['pages'],
                seconds: $bucket['seconds'],
            );
        }

        return $days;
    }
}
