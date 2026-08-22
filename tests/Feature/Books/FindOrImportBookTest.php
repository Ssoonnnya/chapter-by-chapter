<?php

namespace Tests\Feature\Books;

use App\Domain\Books\Actions\FindOrImportBook;
use App\Domain\Books\Exceptions\BookNotFound;
use App\Domain\Books\Exceptions\UnsupportedBookLanguage;
use App\Domain\Books\Models\Book;
use App\Domain\Books\Testing\FakeBookProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookCards;
use Tests\TestCase;

class FindOrImportBookTest extends TestCase
{
    use BuildsBookCards, RefreshDatabase;

    public function test_it_imports_a_volume_without_adding_it_to_a_library(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard());

        $book = app(FindOrImportBook::class)->handle('vol-hobbit');

        $this->assertSame('The Hobbit', $book->title);
        $this->assertDatabaseHas('books', ['google_volume_id' => 'vol-hobbit']);
        $this->assertDatabaseCount('library_items', 0);
    }

    public function test_it_rejects_a_russian_volume(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard([
            'googleVolumeId' => 'vol-ru',
            'language' => 'ru',
        ]));

        try {
            app(FindOrImportBook::class)->handle('vol-ru');
            $this->fail('Russian volumes should be rejected.');
        } catch (UnsupportedBookLanguage) {
            $this->assertDatabaseCount('books', 0);
        }
    }

    public function test_it_rejects_an_unknown_volume(): void
    {
        $this->expectException(BookNotFound::class);

        app(FindOrImportBook::class)->handle('missing');
    }

    public function test_it_returns_a_stored_book_when_the_provider_has_nothing(): void
    {
        $stored = Book::factory()->create(['google_volume_id' => 'vol-local']);

        $book = app(FindOrImportBook::class)->handle('vol-local');

        $this->assertTrue($book->is($stored));
    }

    public function test_it_updates_an_existing_book_row(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard(['title' => 'The Hobbit: Revised']));
        Book::factory()->create([
            'google_volume_id' => 'vol-hobbit',
            'title' => 'Old title',
        ]);

        $book = app(FindOrImportBook::class)->handle('vol-hobbit');

        $this->assertSame('The Hobbit: Revised', $book->title);
        $this->assertSame(1, Book::query()->count());
    }
}
