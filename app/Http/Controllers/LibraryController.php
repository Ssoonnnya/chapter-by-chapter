<?php

namespace App\Http\Controllers;

use App\Domain\Books\Actions\ImportAndAddBook;
use App\Domain\Books\Actions\ListLibrary;
use App\Domain\Books\Actions\SearchBooks;
use App\Domain\Books\Exceptions\BookNotFound;
use App\Domain\Books\Exceptions\UnsupportedBookLanguage;
use App\Domain\Books\Models\LibraryItem;
use App\Http\Requests\Library\StoreLibraryItemRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    public function index(Request $request, ListLibrary $listLibrary): Response
    {
        $items = $listLibrary->handle($this->user($request));

        return Inertia::render('library/Index', [
            'books' => $items->map(fn (LibraryItem $item): array => [
                'id' => $item->id,
                'title' => $item->book->title,
                'authors' => $item->book->authors,
                'description' => $item->book->description,
                'cover_url' => $item->book->cover_url,
                'language' => $item->book->language,
            ])->values()->all(),
        ]);
    }

    public function suggestions(Request $request, SearchBooks $searchBooks): JsonResponse
    {
        $query = $request->string('q')->toString();

        $cards = $searchBooks->handle($query, SearchBooks::SUGGESTION_LIMIT);

        return response()->json([
            'data' => array_map(fn ($card): array => $card->toArray(), $cards),
        ]);
    }

    public function store(StoreLibraryItemRequest $request, ImportAndAddBook $importAndAddBook): RedirectResponse
    {
        try {
            $result = $importAndAddBook->handle(
                $this->user($request),
                $request->validated('google_volume_id'),
            );
        } catch (BookNotFound) {
            return to_route('library.index')->withErrors([
                'google_volume_id' => __('This book could not be found.'),
            ]);
        } catch (UnsupportedBookLanguage) {
            return to_route('library.index')->withErrors([
                'google_volume_id' => __('Russian-language books are not supported.'),
            ]);
        }

        $message = $result->alreadyInLibrary
            ? __('Already in your library.')
            : __('Book added to your library.');

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return to_route('library.index');
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
