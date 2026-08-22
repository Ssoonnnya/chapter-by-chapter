<?php

namespace Tests\Feature\Books;

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Testing\FakeBookProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsBookCards;
use Tests\TestCase;

class BookHttpTest extends TestCase
{
    use BuildsBookCards, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_cannot_view_a_book_page(): void
    {
        $this->get(route('books.show', 'vol-hobbit'))->assertRedirect(route('login'));
    }

    public function test_viewing_a_volume_imports_the_book_without_shelving_it(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('books.show', 'vol-hobbit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('books/Show')
                ->where('book.title', 'The Hobbit')
                ->where('book.description', 'A hobbit goes on an adventure.')
                ->where('libraryItem', null),
            );

        $this->assertDatabaseHas('books', ['google_volume_id' => 'vol-hobbit']);
        $this->assertDatabaseCount('library_items', 0);
    }

    public function test_an_unknown_or_russian_volume_is_not_found(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard([
            'googleVolumeId' => 'vol-ru',
            'language' => 'ru',
        ]));

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('books.show', 'missing'))->assertNotFound();
        $this->actingAs($user)->get(route('books.show', 'vol-ru'))->assertNotFound();
    }

    public function test_adding_from_the_book_page_stays_on_the_book_page(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('books.show', 'vol-hobbit'))
            ->post(route('library.store'), ['google_volume_id' => 'vol-hobbit'])
            ->assertRedirect(route('books.show', 'vol-hobbit'));

        $this->actingAs($user)
            ->get(route('books.show', 'vol-hobbit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('libraryItem.status', 'want'),
            );
    }

    public function test_a_shelved_book_shows_session_controls(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['google_volume_id' => 'vol-owned']);
        LibraryItem::factory()->for($user)->for($book)->reading()->create();

        $this->actingAs($user)
            ->get(route('books.show', 'vol-owned'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('libraryItem.status', 'reading')
                ->where('openSession', null),
            );
    }
}
