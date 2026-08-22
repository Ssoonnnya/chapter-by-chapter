<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Models\ReadingSession;
use App\Models\User;
use Illuminate\Support\Collection;

class ListReadingSessions
{
    /**
     * @return Collection<int, ReadingSession>
     */
    public function handle(User $user, ?int $libraryItemId = null): Collection
    {
        return ReadingSession::query()
            ->where('user_id', $user->id)
            ->when($libraryItemId !== null, fn ($query) => $query->where('library_item_id', $libraryItemId))
            ->with(['libraryItem.book'])
            ->latest('started_at')
            ->get();
    }
}
