<?php

namespace App\Http\Controllers;

use App\Domain\Books\Actions\FindOrImportBook;
use App\Domain\Books\Exceptions\BookNotFound;
use App\Domain\Books\Exceptions\UnsupportedBookLanguage;
use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function show(Request $request, string $googleVolume, FindOrImportBook $findOrImportBook): Response
    {
        try {
            $book = $findOrImportBook->handle($googleVolume);
        } catch (BookNotFound|UnsupportedBookLanguage) {
            abort(404);
        }

        $user = $this->user($request);
        $item = LibraryItem::query()
            ->where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->first();
        $open = ReadingSession::openFor($user);

        return Inertia::render('books/Show', [
            'book' => $this->bookPayload($book),
            'libraryItem' => $item === null ? null : [
                'id' => $item->id,
                'status' => $item->status->value,
                'current_page' => $item->current_page,
                'page_count' => $book->page_count,
            ],
            'openSession' => $open?->toOpenPayload(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function bookPayload(Book $book): array
    {
        return [
            'id' => $book->id,
            'google_volume_id' => $book->google_volume_id,
            'title' => $book->title,
            'authors' => $book->authors,
            'description' => $book->description,
            'cover_url' => $book->cover_url,
            'language' => $book->language,
            'isbn_13' => $book->isbn_13,
            'page_count' => $book->page_count,
            'published_year' => $book->published_year,
        ];
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
