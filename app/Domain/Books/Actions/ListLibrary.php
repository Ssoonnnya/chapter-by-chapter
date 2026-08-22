<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Models\LibraryItem;
use App\Models\User;
use Illuminate\Support\Collection;

class ListLibrary
{
    /**
     * @return Collection<int, LibraryItem>
     */
    public function handle(User $user): Collection
    {
        return $user->libraryItems()
            ->with('book')
            ->latest()
            ->get();
    }
}
