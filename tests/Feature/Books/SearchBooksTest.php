<?php

namespace Tests\Feature\Books;

use App\Domain\Books\Actions\SearchBooks;
use App\Domain\Books\Contracts\BookProvider;
use App\Domain\Books\Testing\FakeBookProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookCards;
use Tests\TestCase;

class SearchBooksTest extends TestCase
{
    use BuildsBookCards, RefreshDatabase;

    public function test_it_does_not_call_the_provider_for_short_queries(): void
    {
        $provider = app(FakeBookProvider::class);
        $provider->add($this->bookCard());

        $results = app(SearchBooks::class)->handle('H');

        $this->assertSame([], $results);
        $this->assertSame([], $provider->searches);
    }

    public function test_it_returns_matches_in_any_language_except_russian(): void
    {
        $provider = app(FakeBookProvider::class);
        $provider->add($this->bookCard([
            'googleVolumeId' => 'uk-1',
            'title' => 'Лісова пісня',
            'language' => 'uk',
        ]));
        $provider->add($this->bookCard([
            'googleVolumeId' => 'fr-1',
            'title' => 'Le Petit Prince',
            'language' => 'fr',
        ]));
        $provider->add($this->bookCard([
            'googleVolumeId' => 'ru-1',
            'title' => 'Лісова пісня',
            'language' => 'ru',
        ]));

        $results = app(SearchBooks::class)->handle('пісня');

        $this->assertCount(1, $results);
        $this->assertSame('uk-1', $results[0]->googleVolumeId);
        $this->assertSame('пісня', $provider->searches[0]['query']);
    }

    public function test_it_filters_russian_cards_even_if_the_provider_returns_them(): void
    {
        $provider = new class extends FakeBookProvider
        {
            public function search(string $query, int $limit): array
            {
                $this->searches[] = ['query' => $query, 'limit' => $limit];

                return $this->volumes;
            }
        };
        $provider->add($this->bookCard(['googleVolumeId' => 'ru-2', 'language' => 'rus']));
        $this->app->instance(FakeBookProvider::class, $provider);
        $this->app->instance(BookProvider::class, $provider);

        $results = app(SearchBooks::class)->handle('Hobbit');

        $this->assertSame([], $results);
    }
}
