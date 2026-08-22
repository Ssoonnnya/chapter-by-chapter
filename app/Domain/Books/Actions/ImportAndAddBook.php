<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Data\AddBookResult;
use App\Domain\Books\Enums\LibraryStatus;
use App\Domain\Books\Models\LibraryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ImportAndAddBook
{
    public function __construct(private readonly FindOrImportBook $findOrImportBook) {}

    public function handle(User $user, string $googleVolumeId): AddBookResult
    {
        return DB::transaction(function () use ($user, $googleVolumeId): AddBookResult {
            $book = $this->findOrImportBook->handle($googleVolumeId);

            $item = LibraryItem::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                ],
                [
                    'status' => LibraryStatus::Want,
                    'current_page' => 0,
                ],
            );

            return new AddBookResult(
                book: $book,
                alreadyInLibrary: ! $item->wasRecentlyCreated,
            );
        });
    }
}
