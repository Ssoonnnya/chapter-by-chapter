<?php

namespace App\Http\Controllers;

use App\Domain\Books\Actions\CancelReadingSession;
use App\Domain\Books\Actions\ImportAndAddBook;
use App\Domain\Books\Actions\ListLibrary;
use App\Domain\Books\Actions\PauseReadingSession;
use App\Domain\Books\Actions\ResumeReadingSession;
use App\Domain\Books\Actions\SearchBooks;
use App\Domain\Books\Actions\StartReadingSession;
use App\Domain\Books\Actions\StopReadingSession;
use App\Domain\Books\Actions\UpdateLibraryItemPage;
use App\Domain\Books\Actions\UpdateLibraryItemStatus;
use App\Domain\Books\Data\BookCard;
use App\Domain\Books\Enums\LibraryStatus;
use App\Domain\Books\Exceptions\BookNotFound;
use App\Domain\Books\Exceptions\BookNotReading;
use App\Domain\Books\Exceptions\InvalidLibraryPage;
use App\Domain\Books\Exceptions\LibraryItemNotFound;
use App\Domain\Books\Exceptions\NoOpenSession;
use App\Domain\Books\Exceptions\SessionAlreadyOpen;
use App\Domain\Books\Exceptions\SessionAlreadyPaused;
use App\Domain\Books\Exceptions\SessionNotPaused;
use App\Domain\Books\Exceptions\UnsupportedBookLanguage;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Http\Requests\Library\StartReadingSessionRequest;
use App\Http\Requests\Library\StopReadingSessionRequest;
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

        $open = ReadingSession::openFor($this->user($request));

        return Inertia::render('library/Index', [
            'filter' => $status?->value,
            'openSession' => $open?->toOpenPayload(),
            'books' => $items->map(fn (LibraryItem $item): array => [
                'id' => $item->id,
                'google_volume_id' => $item->book->google_volume_id,
                'title' => $item->book->title,
                'authors' => $item->book->authors,
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
        $inLibrary = $this->volumeIdsOnShelf($this->user($request), $cards);

        return response()->json([
            'data' => array_map(
                fn ($card): array => [
                    ...$card->toArray(),
                    'in_library' => in_array($card->googleVolumeId, $inLibrary, true),
                ],
                $cards,
            ),
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
            return back()->withErrors([
                'google_volume_id' => __('This book could not be found.'),
            ]);
        } catch (UnsupportedBookLanguage) {
            return back()->withErrors([
                'google_volume_id' => __('Russian-language books are not supported.'),
            ]);
        }

        $message = $result->alreadyInLibrary
            ? __('Already in your library.')
            : __('Book added to your library.');

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
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

    public function startSession(
        StartReadingSessionRequest $request,
        int $libraryItem,
        StartReadingSession $start,
    ): RedirectResponse {
        try {
            $start->handle(
                $this->user($request),
                $libraryItem,
                (int) $request->validated('start_page'),
            );
        } catch (LibraryItemNotFound) {
            abort(404);
        } catch (BookNotReading) {
            return back()->withErrors([
                'session' => __('Start a session only on a book you are reading.'),
            ]);
        } catch (SessionAlreadyOpen) {
            return back()->withErrors([
                'session' => __('A reading session is already open.'),
            ]);
        } catch (InvalidLibraryPage) {
            return back()->withErrors([
                'start_page' => __('That page number is not valid for this book.'),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reading session started.')]);

        return back();
    }

    public function pauseSession(
        Request $request,
        int $libraryItem,
        PauseReadingSession $pause,
    ): RedirectResponse {
        try {
            $pause->handle($this->user($request), $libraryItem);
        } catch (NoOpenSession) {
            abort(404);
        } catch (SessionAlreadyPaused) {
            return back()->withErrors([
                'session' => __('This reading session is already paused.'),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reading paused.')]);

        return back();
    }

    public function resumeSession(
        Request $request,
        int $libraryItem,
        ResumeReadingSession $resume,
    ): RedirectResponse {
        try {
            $resume->handle($this->user($request), $libraryItem);
        } catch (NoOpenSession) {
            abort(404);
        } catch (SessionNotPaused) {
            return back()->withErrors([
                'session' => __('This reading session is not paused.'),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reading resumed.')]);

        return back();
    }

    public function stopSession(
        StopReadingSessionRequest $request,
        int $libraryItem,
        StopReadingSession $stop,
    ): RedirectResponse {
        try {
            $stop->handle(
                $this->user($request),
                (int) $request->validated('end_page'),
                $libraryItem,
            );
        } catch (NoOpenSession) {
            abort(404);
        } catch (InvalidLibraryPage) {
            return back()->withErrors([
                'end_page' => __('That page number is not valid for this book.'),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reading session saved.')]);

        return back();
    }

    public function cancelSession(
        Request $request,
        int $libraryItem,
        CancelReadingSession $cancel,
    ): RedirectResponse {
        try {
            $cancel->handle($this->user($request), $libraryItem);
        } catch (NoOpenSession) {
            abort(404);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reading session cancelled.')]);

        return back();
    }

    /**
     * @param  list<BookCard>  $cards
     * @return list<string>
     */
    private function volumeIdsOnShelf(User $user, array $cards): array
    {
        $volumeIds = array_map(fn ($card): string => $card->googleVolumeId, $cards);

        if ($volumeIds === []) {
            return [];
        }

        return array_values($user->libraryItems()
            ->whereHas('book', fn ($query) => $query->whereIn('google_volume_id', $volumeIds))
            ->with('book')
            ->get()
            ->map(fn (LibraryItem $item): string => $item->book->google_volume_id)
            ->all());
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
