<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Contracts\BookProvider;
use App\Domain\Books\Exceptions\BookNotFound;
use App\Domain\Books\Exceptions\UnsupportedBookLanguage;
use App\Domain\Books\Language\BlockedBookLanguages;
use App\Domain\Books\Models\Book;

class FindOrImportBook
{
    public function __construct(private readonly BookProvider $provider) {}

    public function handle(string $googleVolumeId): Book
    {
        $existing = Book::query()->where('google_volume_id', $googleVolumeId)->first();
        $card = $this->provider->find($googleVolumeId);

        if ($card === null) {
            if ($existing instanceof Book) {
                return $existing;
            }

            throw new BookNotFound;
        }

        if (BlockedBookLanguages::contains($card->language)) {
            throw new UnsupportedBookLanguage;
        }

        return Book::query()->updateOrCreate(
            ['google_volume_id' => $card->googleVolumeId],
            [
                'isbn_13' => $card->isbn13,
                'title' => $card->title,
                'authors' => $card->authors,
                'description' => $card->description,
                'cover_url' => $card->coverUrl,
                'language' => $card->language,
                'page_count' => $card->pageCount,
                'published_year' => $card->publishedYear,
            ],
        );
    }
}
