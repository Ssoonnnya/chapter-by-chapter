<?php

namespace Tests\Feature\Books;

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Domain\Books\Testing\FakeBookProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
                ->where('libraryItem', null)
                ->where('sessions', []),
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
                ->where('openSession', null)
                ->where('sessions', []),
            );
    }

    public function test_a_shelved_book_lists_each_stopped_session_with_its_own_pace(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create(['google_volume_id' => 'vol-owned']);
        $item = LibraryItem::factory()->for($user)->for($book)->reading()->create();

        ReadingSession::factory()->for($user)->for($item, 'libraryItem')->stopped(20)->create([
            'start_page' => 10,
            'started_at' => Carbon::parse('2026-07-01 10:00:00'),
            'ended_at' => Carbon::parse('2026-07-01 11:00:00'),
        ]);
        ReadingSession::factory()->for($user)->for($item, 'libraryItem')->stopped(40)->create([
            'start_page' => 20,
            'started_at' => Carbon::parse('2026-08-02 10:00:00'),
            'ended_at' => Carbon::parse('2026-08-02 10:30:00'),
        ]);
        ReadingSession::factory()->for($user)->for($item, 'libraryItem')->create([
            'start_page' => 40,
            'started_at' => Carbon::parse('2026-08-03 10:00:00'),
            'ended_at' => Carbon::parse('2026-08-03 11:00:00'),
            'end_page' => null,
        ]);
        ReadingSession::factory()->for($user)->for($item, 'libraryItem')->create([
            'start_page' => 40,
            'started_at' => now(),
            'ended_at' => null,
            'end_page' => null,
        ]);

        $this->actingAs($user)
            ->get(route('books.show', 'vol-owned'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('sessions', 2)
                ->where('sessions.0.pages', 20)
                ->where('sessions.0.minutes', 30)
                ->where('sessions.0.pages_per_hour', 40)
                ->where('sessions.1.pages', 10)
                ->where('sessions.1.minutes', 60)
                ->where('sessions.1.pages_per_hour', 10)
                ->missing('lifetime'),
            );
    }
}
