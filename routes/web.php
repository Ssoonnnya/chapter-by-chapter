<?php

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

    Route::get('library', [LibraryController::class, 'index'])->name('library.index');
    Route::get('library/suggestions', [LibraryController::class, 'suggestions'])
        ->middleware('throttle:book-search')
        ->name('library.suggestions');
    Route::post('library', [LibraryController::class, 'store'])->name('library.store');
    Route::patch('library/{libraryItem}/status', [LibraryController::class, 'updateStatus'])->name('library.status');
    Route::patch('library/{libraryItem}/page', [LibraryController::class, 'updatePage'])->name('library.page');
});

require __DIR__.'/settings.php';
