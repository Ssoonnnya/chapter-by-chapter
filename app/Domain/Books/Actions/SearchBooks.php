<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Contracts\BookProvider;
use App\Domain\Books\Data\BookCard;
use App\Domain\Books\Language\BlockedBookLanguages;

class SearchBooks
{
    public const MIN_QUERY_LENGTH = 2;

    public const SUGGESTION_LIMIT = 8;

    public const MAX_LIMIT = 20;

    public function __construct(private readonly BookProvider $provider) {}

    /**
     * @return list<BookCard>
     */
    public function handle(string $query, int $limit = self::SUGGESTION_LIMIT): array
    {
        $query = trim($query);

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return [];
        }

        $limit = max(1, min($limit, self::MAX_LIMIT));

        $cards = $this->provider->search($query, $limit);

        return array_values(array_filter(
            $cards,
            fn (BookCard $card): bool => ! BlockedBookLanguages::contains($card->language),
        ));
    }
}
