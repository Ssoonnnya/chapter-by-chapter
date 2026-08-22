<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Exceptions\NoOpenSession;
use App\Domain\Books\Models\ReadingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CancelReadingSession
{
    public function handle(User $user, ?int $libraryItemId = null): ReadingSession
    {
        return DB::transaction(function () use ($user, $libraryItemId): ReadingSession {
            $session = ReadingSession::query()
                ->open()
                ->where('user_id', $user->id)
                ->when($libraryItemId !== null, fn ($query) => $query->where('library_item_id', $libraryItemId))
                ->with(['libraryItem.book'])
                ->lockForUpdate()
                ->first();

            if ($session === null) {
                throw new NoOpenSession;
            }

            $session->update(['ended_at' => now()]);

            return $session->refresh()->load('libraryItem.book');
        });
    }
}
