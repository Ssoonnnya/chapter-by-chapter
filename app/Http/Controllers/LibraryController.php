<?php

namespace App\Http\Controllers;

use App\Domain\Books\Actions\ImportAndAddBook;
use App\Domain\Books\Actions\ListLibrary;
use App\Domain\Books\Actions\SearchBooks;
use App\Domain\Books\Actions\UpdateLibraryItemPage;
use App\Domain\Books\Actions\UpdateLibraryItemStatus;
use App\Domain\Books\Enums\LibraryStatus;
use App\Domain\Books\Exceptions\BookNotFound;
use App\Domain\Books\Exceptions\InvalidLibraryPage;
use App\Domain\Books\Exceptions\LibraryItemNotFound;
use App\Domain\Books\Exceptions\UnsupportedBookLanguage;
use App\Domain\Books\Models\LibraryItem;
use App\Http\Requests\Library\StoreLibraryItemRequest;
use App\Http\Requests\Library\UpdateLibraryPageRequest;
use App\Http\Requests\Library\UpdateLibraryStatusRequest;
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
        $status = LibraryStatus::tryFrom((string) $request->query('status', ''));
        $items = $listLibrary->handle($this->user($request), $status);

        return Inertia::render('library/Index', [
            'filter' => $status?->value,
            'books' => $items->map(fn (LibraryItem $item): array => [
                'id' => $item->id,
                'title' => $item->book->title,
                'authors' => $item->book->authors,
                'description' => $item->book->description,
                'cover_url' => $item->book->cover_url,
                'language' => $item->book->language,
                'status' => $item->status->value,
                'current_page' => $item->current_page,
                'page_count' => $item->book->page_count,
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

    public function updateStatus(
        UpdateLibraryStatusRequest $request,
        int $libraryItem,
        UpdateLibraryItemStatus $updateStatus,
    ): RedirectResponse {
        try {
            $updateStatus->handle(
                $this->user($request),
                $libraryItem,
                LibraryStatus::from($request->validated('status')),
            );
        } catch (LibraryItemNotFound) {
            abort(404);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Shelf updated.')]);

        return back();
    }

    public function updatePage(
        UpdateLibraryPageRequest $request,
        int $libraryItem,
        UpdateLibraryItemPage $updatePage,
    ): RedirectResponse {
        try {
            $updatePage->handle(
                $this->user($request),
                $libraryItem,
                (int) $request->validated('current_page'),
            );
        } catch (LibraryItemNotFound) {
            abort(404);
        } catch (InvalidLibraryPage) {
            return back()->withErrors([
                'current_page' => __('That page number is not valid for this book.'),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page updated.')]);

        return back();
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
