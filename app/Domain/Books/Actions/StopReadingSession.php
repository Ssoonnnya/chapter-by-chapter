<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Exceptions\InvalidLibraryPage;
use App\Domain\Books\Exceptions\NoOpenSession;
use App\Domain\Books\Models\ReadingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StopReadingSession
{
    public function handle(User $user, int $endPage, ?int $libraryItemId = null): ReadingSession
    {
        if ($endPage < 0) {
            throw new InvalidLibraryPage;
        }

        return DB::transaction(function () use ($user, $endPage, $libraryItemId): ReadingSession {
            $session = $this->openSession($user, $libraryItemId);
            $item = $session->libraryItem;
            $pageCount = $item->book->page_count;

            if ($endPage < $session->start_page) {
                throw new InvalidLibraryPage;
            }

            if ($pageCount !== null && $endPage > $pageCount) {
                throw new InvalidLibraryPage;
            }

            $session->closeOpenPause();
            $session->update([
                'ended_at' => now(),
                'end_page' => $endPage,
                'paused_at' => $session->paused_at,
                'paused_seconds' => $session->paused_seconds,
            ]);

            $item->update(['current_page' => $endPage]);

            return $session->refresh()->load('libraryItem.book');
        });
    }

    private function openSession(User $user, ?int $libraryItemId): ReadingSession
    {
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

        return $session;
    }
}
