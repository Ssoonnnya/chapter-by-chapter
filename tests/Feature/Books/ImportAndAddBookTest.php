<?php

namespace Tests\Feature\Books;

use App\Domain\Books\Actions\ImportAndAddBook;
use App\Domain\Books\Exceptions\BookNotFound;
use App\Domain\Books\Exceptions\UnsupportedBookLanguage;
use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Testing\FakeBookProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookCards;
use Tests\TestCase;

class ImportAndAddBookTest extends TestCase
{
    use BuildsBookCards, RefreshDatabase;

    public function test_it_imports_a_volume_and_adds_it_to_the_library(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard());
        $user = User::factory()->create();

        $result = app(ImportAndAddBook::class)->handle($user, 'vol-hobbit');

        $this->assertFalse($result->alreadyInLibrary);
        $this->assertSame('The Hobbit', $result->book->title);
        $this->assertDatabaseHas('books', [
            'google_volume_id' => 'vol-hobbit',
            'language' => 'en',
        ]);
        $this->assertDatabaseHas('library_items', [
            'user_id' => $user->id,
            'book_id' => $result->book->id,
        ]);
    }

    public function test_it_does_not_duplicate_an_existing_library_item(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard());
        $user = User::factory()->create();
        $action = app(ImportAndAddBook::class);

        $action->handle($user, 'vol-hobbit');
        $second = $action->handle($user, 'vol-hobbit');

        $this->assertTrue($second->alreadyInLibrary);
        $this->assertSame(1, Book::query()->count());
        $this->assertSame(1, LibraryItem::query()->where('user_id', $user->id)->count());
    }

    public function test_it_rejects_a_russian_volume(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard([
            'googleVolumeId' => 'vol-ru',
            'language' => 'ru',
        ]));

        try {
            app(ImportAndAddBook::class)->handle(User::factory()->create(), 'vol-ru');
            $this->fail('Russian volumes should be rejected.');
        } catch (UnsupportedBookLanguage) {
            $this->assertDatabaseCount('books', 0);
            $this->assertDatabaseCount('library_items', 0);
        }
    }

    public function test_it_rejects_an_unknown_volume(): void
    {
        $this->expectException(BookNotFound::class);

        app(ImportAndAddBook::class)->handle(User::factory()->create(), 'missing');
    }
}
