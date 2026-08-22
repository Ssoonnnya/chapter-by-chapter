<?php

namespace App\Providers;

use App\Domain\Books\Contracts\BookProvider;
use App\Domain\Books\Providers\GoogleBooksProvider;
use App\Domain\Books\Testing\FakeBookProvider;
use Illuminate\Support\ServiceProvider;

class BooksServiceProvider extends ServiceProvider
{
    /**
     * Register book provider bindings.
     */
    public function register(): void
    {
        $this->app->singleton(FakeBookProvider::class);

        $this->app->singleton(
            BookProvider::class,
            fn (): BookProvider => $this->app->runningUnitTests()
                ? $this->app->make(FakeBookProvider::class)
                : $this->app->make(GoogleBooksProvider::class),
        );
    }
}
