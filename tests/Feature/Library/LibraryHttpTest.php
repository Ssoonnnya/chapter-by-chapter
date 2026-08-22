<?php

namespace Tests\Feature\Library;

use App\Domain\Books\Models\LibraryItem;
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
            ->assertJsonPath('data.0.language', 'fr');
    }

    public function test_user_can_add_a_book_from_search(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard());
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('library.store'), ['google_volume_id' => 'vol-hobbit'])
            ->assertRedirect(route('library.index'));

        $this->assertDatabaseHas('library_items', ['user_id' => $user->id]);
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
}
