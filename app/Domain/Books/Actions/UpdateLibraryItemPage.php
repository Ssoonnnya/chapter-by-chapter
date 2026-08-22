<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Exceptions\InvalidLibraryPage;
use App\Domain\Books\Exceptions\LibraryItemNotFound;
use App\Domain\Books\Models\LibraryItem;
use App\Models\User;

class UpdateLibraryItemPage
{
    public function handle(User $user, int $libraryItemId, int $page): LibraryItem
    {
        if ($page < 0) {
            throw new InvalidLibraryPage;
        }

        $item = LibraryItem::query()
            ->where('user_id', $user->id)
            ->with('book')
            ->find($libraryItemId);

        if ($item === null) {
            throw new LibraryItemNotFound;
        }

        $pageCount = $item->book->page_count;

        if ($pageCount !== null && $page > $pageCount) {
            throw new InvalidLibraryPage;
        }

        $item->update(['current_page' => $page]);

        return $item->refresh();
    }
}
