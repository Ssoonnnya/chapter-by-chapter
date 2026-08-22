<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\LibraryController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('library.index');
    }

    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/library')->name('dashboard');

    Route::get('books/{googleVolume}', [BookController::class, 'show'])->name('books.show');

    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');

    Route::get('library', [LibraryController::class, 'index'])->name('library.index');
    Route::get('library/suggestions', [LibraryController::class, 'suggestions'])
        ->middleware('throttle:book-search')
        ->name('library.suggestions');
    Route::post('library', [LibraryController::class, 'store'])->name('library.store');
    Route::patch('library/{libraryItem}/status', [LibraryController::class, 'updateStatus'])->name('library.status');
    Route::patch('library/{libraryItem}/page', [LibraryController::class, 'updatePage'])->name('library.page');
    Route::post('library/{libraryItem}/session', [LibraryController::class, 'startSession'])->name('library.session.start');
    Route::post('library/{libraryItem}/session/stop', [LibraryController::class, 'stopSession'])->name('library.session.stop');
    Route::post('library/{libraryItem}/session/pause', [LibraryController::class, 'pauseSession'])->name('library.session.pause');
    Route::post('library/{libraryItem}/session/resume', [LibraryController::class, 'resumeSession'])->name('library.session.resume');
    Route::delete('library/{libraryItem}/session', [LibraryController::class, 'cancelSession'])->name('library.session.cancel');
});

require __DIR__.'/settings.php';
