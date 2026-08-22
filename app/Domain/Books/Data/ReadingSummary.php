<?php

namespace App\Domain\Books\Data;

final readonly class ReadingSummary
{
    /**
     * @param  list<ReadingDay>  $days
     */
    public function __construct(
        public ?string $month,
        public int $sessions,
        public int $pages,
        public int $seconds,
        public ?float $pagesPerHour,
        public array $days,
    ) {}

    public function minutes(): int
    {
        return intdiv($this->seconds, 60);
    }

    /**
     * @return array{
     *     month: string|null,
     *     sessions: int,
     *     pages: int,
     *     seconds: int,
     *     minutes: int,
     *     pages_per_hour: float|null,
     *     days: list<array{date: string, sessions: int, pages: int, seconds: int, minutes: int}>
     * }
     */
    public function toArray(): array
    {
        return [
            'month' => $this->month,
            'sessions' => $this->sessions,
            'pages' => $this->pages,
            'seconds' => $this->seconds,
            'minutes' => $this->minutes(),
            'pages_per_hour' => $this->pagesPerHour,
            'days' => array_map(
                fn (ReadingDay $day): array => $day->toArray(),
                $this->days,
            ),
        ];
    }
}
