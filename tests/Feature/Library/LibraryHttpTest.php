<?php

namespace Tests\Feature\Library;

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Domain\Books\Testing\FakeBookProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsBookCards;
use Tests\TestCase;

class LibraryHttpTest extends TestCase
{
    use BuildsBookCards, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_cannot_view_the_library(): void
    {
        $this->get(route('library.index'))->assertRedirect(route('login'));
        $this->getJson(route('library.suggestions', ['q' => 'hobbit']))->assertUnauthorized();
        $this->post(route('library.store'), ['google_volume_id' => 'x'])->assertRedirect(route('login'));
    }

    public function test_library_page_lists_added_books(): void
    {
        $user = User::factory()->create();
        LibraryItem::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('library.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('library/Index')
                ->where('openSession', null)
                ->has('books', 1),
            );
    }

    public function test_suggestions_return_cards_without_using_account_locale(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard([
            'title' => 'Le Petit Prince',
            'language' => 'fr',
        ]));

        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->getJson(route('library.suggestions', ['q' => 'Prince']))
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Le Petit Prince')
            ->assertJsonPath('data.0.language', 'fr')
            ->assertJsonPath('data.0.in_library', false);
    }

    public function test_user_can_add_a_book_from_search(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('library.index'))
            ->post(route('library.store'), ['google_volume_id' => 'vol-hobbit'])
            ->assertRedirect(route('library.index'));

        $this->assertDatabaseHas('library_items', [
            'user_id' => $user->id,
            'status' => 'want',
            'current_page' => 0,
        ]);
    }

    public function test_library_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        LibraryItem::factory()->for($user)->create();
        LibraryItem::factory()->for($user)->reading()->create();

        $this->actingAs($user)
            ->get(route('library.index', ['status' => 'reading']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('library/Index')
                ->where('filter', 'reading')
                ->has('books', 1)
                ->where('books.0.status', 'reading'),
            );
    }

    public function test_user_can_update_status_and_page(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->for(
            Book::factory()->create(['page_count' => 300]),
        )->create();

        $this->actingAs($user)
            ->patch(route('library.status', $item), ['status' => 'reading'])
            ->assertRedirect();

        $this->actingAs($user)
            ->from(route('library.index'))
            ->patch(route('library.page', $item), ['current_page' => 25])
            ->assertRedirect();

        $item->refresh();
        $this->assertSame('reading', $item->status->value);
        $this->assertSame(25, $item->current_page);
    }

    public function test_user_cannot_update_someone_elses_item(): void
    {
        $item = LibraryItem::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('library.status', $item), ['status' => 'finished'])
            ->assertNotFound();
    }

    public function test_adding_a_russian_volume_is_rejected(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard([
            'googleVolumeId' => 'vol-ru',
            'language' => 'ru',
        ]));

        $this->actingAs(User::factory()->create())
            ->from(route('library.index'))
            ->post(route('library.store'), ['google_volume_id' => 'vol-ru'])
            ->assertRedirect(route('library.index'))
            ->assertSessionHasErrors('google_volume_id');

        $this->assertDatabaseCount('books', 0);
    }

    public function test_suggestions_mark_books_already_on_the_shelf(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard());
        $user = User::factory()->create();
        $book = Book::factory()->create(['google_volume_id' => 'vol-hobbit']);
        LibraryItem::factory()->for($user)->for($book)->create();

        $this->actingAs($user)
            ->getJson(route('library.suggestions', ['q' => 'hobbit']))
            ->assertOk()
            ->assertJsonPath('data.0.in_library', true);
    }

    public function test_user_can_start_stop_and_cancel_a_session(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->for(
            Book::factory()->create(['page_count' => 200]),
        )->reading()->create(['current_page' => 4]);

        $this->actingAs($user)
            ->from(route('library.index'))
            ->post(route('library.session.start', $item), ['start_page' => 4])
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('library.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('openSession.library_item_id', $item->id)
                ->where('openSession.start_page', 4)
                ->where('openSession.paused_at', null),
            );

        $this->actingAs($user)
            ->from(route('library.index'))
            ->post(route('library.session.stop', $item), ['end_page' => 18])
            ->assertRedirect();

        $this->assertSame(18, $item->fresh()?->current_page);
        $this->assertDatabaseHas('reading_sessions', [
            'library_item_id' => $item->id,
            'end_page' => 18,
        ]);

        $this->actingAs($user)
            ->post(route('library.session.start', $item), ['start_page' => 18])
            ->assertRedirect();

        $this->actingAs($user)
            ->from(route('library.index'))
            ->delete(route('library.session.cancel', $item))
            ->assertRedirect();

        $this->assertSame(18, $item->fresh()?->current_page);
        $this->assertNull(ReadingSession::openFor($user));
    }

    public function test_user_can_pause_and_resume_a_session(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->reading()->create();

        $this->actingAs($user)
            ->post(route('library.session.start', $item), ['start_page' => 2])
            ->assertRedirect();

        $this->actingAs($user)
            ->post(route('library.session.pause', $item))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('library.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->whereNot('openSession.paused_at', null),
            );

        $this->actingAs($user)
            ->post(route('library.session.resume', $item))
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('library.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('openSession.paused_at', null),
            );
    }

    public function test_user_cannot_start_a_session_on_someone_elses_book(): void
    {
        $item = LibraryItem::factory()->reading()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('library.session.start', $item), ['start_page' => 0])
            ->assertNotFound();
    }
}
