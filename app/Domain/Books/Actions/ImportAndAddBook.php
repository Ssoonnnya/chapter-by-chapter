<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Contracts\BookProvider;
use App\Domain\Books\Data\AddBookResult;
use App\Domain\Books\Exceptions\BookNotFound;
use App\Domain\Books\Exceptions\UnsupportedBookLanguage;
use App\Domain\Books\Language\BlockedBookLanguages;
use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\LibraryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportAndAddBook
{
    public function __construct(private readonly BookProvider $provider) {}

    public function handle(User $user, string $googleVolumeId): AddBookResult
    {
        $card = $this->provider->find($googleVolumeId);

        if ($card === null) {
            throw new BookNotFound;
        }

        if (BlockedBookLanguages::contains($card->language)) {
            throw new UnsupportedBookLanguage;
        }

        return DB::transaction(function () use ($user, $card): AddBookResult {
            $book = Book::query()->updateOrCreate(
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

            $item = LibraryItem::query()->firstOrCreate([
                'user_id' => $user->id,
                'book_id' => $book->id,
            ]);

            return new AddBookResult(
                book: $book,
                alreadyInLibrary: ! $item->wasRecentlyCreated,
            );
        });
    }
}
