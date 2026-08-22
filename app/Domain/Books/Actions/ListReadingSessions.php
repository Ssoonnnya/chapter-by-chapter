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
    public function handle(User $user, ?int $libraryItemId = null, bool $stoppedOnly = false): Collection
    {
        return ReadingSession::query()
            ->where('user_id', $user->id)
            ->when($libraryItemId !== null, fn ($query) => $query->where('library_item_id', $libraryItemId))
            ->when($stoppedOnly, fn ($query) => $query->stopped()->latest('ended_at'), fn ($query) => $query->latest('started_at'))
            ->with(['libraryItem.book'])
            ->get();
    }
}
