<?php

namespace App\Domain\Books\Testing;

use App\Domain\Books\Contracts\BookProvider;
use App\Domain\Books\Data\BookCard;
use App\Domain\Books\Language\BlockedBookLanguages;

class FakeBookProvider implements BookProvider
{
    /**
     * @var list<BookCard>
     */
    public array $volumes = [];

    /**
     * @var list<array{query: string, limit: int}>
     */
    public array $searches = [];

    /**
     * @return list<BookCard>
     */
    public function search(string $query, int $limit): array
    {
        $this->searches[] = ['query' => $query, 'limit' => $limit];

        $needle = mb_strtolower($query);
        $matches = [];

        foreach ($this->volumes as $card) {
            if (BlockedBookLanguages::contains($card->language)) {
                continue;
            }

            $haystack = mb_strtolower($card->title.' '.implode(' ', $card->authors));

            if (! str_contains($haystack, $needle)) {
                continue;
            }

            $matches[] = $card;

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    public function find(string $googleVolumeId): ?BookCard
    {
        foreach ($this->volumes as $card) {
            if ($card->googleVolumeId === $googleVolumeId) {
                return $card;
            }
        }

        return null;
    }

    public function add(BookCard $card): void
    {
        $this->volumes[] = $card;
    }
}
