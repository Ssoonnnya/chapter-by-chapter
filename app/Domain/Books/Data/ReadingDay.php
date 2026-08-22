<?php

namespace App\Domain\Books\Data;

final readonly class ReadingDay
{
    public function __construct(
        public string $date,
        public int $sessions,
        public int $pages,
        public int $seconds,
    ) {}

    public function minutes(): int
    {
        return intdiv($this->seconds, 60);
    }

    /**
     * @return array{date: string, sessions: int, pages: int, seconds: int, minutes: int}
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'sessions' => $this->sessions,
            'pages' => $this->pages,
            'seconds' => $this->seconds,
            'minutes' => $this->minutes(),
        ];
    }
}
