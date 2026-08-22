<?php

namespace Tests\Concerns;

use App\Domain\Books\Data\BookCard;

trait BuildsBookCards
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function bookCard(array $overrides = []): BookCard
    {
        return new BookCard(
            googleVolumeId: $overrides['googleVolumeId'] ?? 'vol-hobbit',
            title: $overrides['title'] ?? 'The Hobbit',
            authors: $overrides['authors'] ?? ['J. R. R. Tolkien'],
            description: $overrides['description'] ?? 'A hobbit goes on an adventure.',
            coverUrl: $overrides['coverUrl'] ?? 'https://example.com/hobbit.jpg',
            language: $overrides['language'] ?? 'en',
            isbn13: $overrides['isbn13'] ?? '9780547928227',
            pageCount: $overrides['pageCount'] ?? 310,
            publishedYear: $overrides['publishedYear'] ?? 1937,
        );
    }
}
