<?php

namespace App\Domain\Books\Actions;

use App\Domain\Books\Enums\LibraryStatus;
use App\Domain\Books\Exceptions\BookNotReading;
use App\Domain\Books\Exceptions\InvalidLibraryPage;
use App\Domain\Books\Exceptions\LibraryItemNotFound;
use App\Domain\Books\Exceptions\SessionAlreadyOpen;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StartReadingSession
{
    public function handle(User $user, int $libraryItemId, int $startPage): ReadingSession
    {
        if ($startPage < 0) {
            throw new InvalidLibraryPage;
        }

        return DB::transaction(function () use ($user, $libraryItemId, $startPage): ReadingSession {
            $open = ReadingSession::query()
                ->open()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($open !== null) {
                throw new SessionAlreadyOpen;
            }

            $item = LibraryItem::query()
                ->where('user_id', $user->id)
                ->with('book')
                ->find($libraryItemId);

            if ($item === null) {
                throw new LibraryItemNotFound;
            }

            if ($item->status !== LibraryStatus::Reading) {
                throw new BookNotReading;
            }

            $pageCount = $item->book->page_count;

            if ($pageCount !== null && $startPage > $pageCount) {
                throw new InvalidLibraryPage;
            }

            return ReadingSession::query()->create([
                'user_id' => $user->id,
                'library_item_id' => $item->id,
                'started_at' => now(),
                'start_page' => $startPage,
                'paused_seconds' => 0,
            ])->load('libraryItem.book');
        });
    }
}
