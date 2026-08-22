<?php

use App\Http\Controllers\LibraryController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('library', [LibraryController::class, 'index'])->name('library.index');
    Route::get('library/suggestions', [LibraryController::class, 'suggestions'])
        ->middleware('throttle:book-search')
        ->name('library.suggestions');
    Route::post('library', [LibraryController::class, 'store'])->name('library.store');
});

require __DIR__.'/settings.php';
