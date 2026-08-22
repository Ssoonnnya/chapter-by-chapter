<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Enums\LibraryStatus;
use App\Domain\Books\Exceptions\LibraryItemNotFound;
use App\Domain\Books\Models\LibraryItem;
use App\Models\User;

class UpdateLibraryItemStatus
{
    public function handle(User $user, int $libraryItemId, LibraryStatus $status): LibraryItem
    {
        $item = LibraryItem::query()
            ->where('user_id', $user->id)
            ->with('book')
            ->find($libraryItemId);

        if ($item === null) {
            throw new LibraryItemNotFound;
        }

        $item->update(['status' => $status]);

        return $item->refresh();
    }
}
