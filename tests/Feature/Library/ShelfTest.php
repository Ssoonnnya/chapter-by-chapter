<?php

namespace Tests\Feature\Library;

use App\Domain\Books\Actions\ImportAndAddBook;
use App\Domain\Books\Actions\ListLibrary;
use App\Domain\Books\Actions\UpdateLibraryItemPage;
use App\Domain\Books\Actions\UpdateLibraryItemStatus;
use App\Domain\Books\Enums\LibraryStatus;
use App\Domain\Books\Exceptions\InvalidLibraryPage;
use App\Domain\Books\Exceptions\LibraryItemNotFound;
use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Testing\FakeBookProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookCards;
use Tests\TestCase;

class ShelfTest extends TestCase
{
    use BuildsBookCards, RefreshDatabase;

    public function test_a_new_library_item_is_want_to_read_on_page_zero(): void
    {
        app(FakeBookProvider::class)->add($this->bookCard());
        $user = User::factory()->create();

        $result = app(ImportAndAddBook::class)->handle($user, 'vol-hobbit');

        $this->assertSame(LibraryStatus::Want, $result->book->libraryItems()->first()?->status);
        $this->assertSame(0, $result->book->libraryItems()->first()?->current_page);
    }

    public function test_it_updates_status_for_the_owner_only(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $item = LibraryItem::factory()->for($owner)->create();

        $updated = app(UpdateLibraryItemStatus::class)->handle($owner, $item->id, LibraryStatus::Reading);

        $this->assertSame(LibraryStatus::Reading, $updated->status);

        try {
            app(UpdateLibraryItemStatus::class)->handle($other, $item->id, LibraryStatus::Finished);
            $this->fail('Other users must not update a shelf item.');
        } catch (LibraryItemNotFound) {
            $this->assertSame(LibraryStatus::Reading, $item->fresh()?->status);
        }
    }

    public function test_it_rejects_a_page_above_the_book_length(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->for(Book::factory()->create(['page_count' => 100]))->create();

        try {
            app(UpdateLibraryItemPage::class)->handle($user, $item->id, 101);
            $this->fail('Pages past the end of the book should be rejected.');
        } catch (InvalidLibraryPage) {
            $this->assertSame(0, $item->fresh()?->current_page);
        }
    }

    public function test_it_allows_any_page_when_page_count_is_unknown(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->for(Book::factory()->create(['page_count' => null]))->create();

        $updated = app(UpdateLibraryItemPage::class)->handle($user, $item->id, 40);

        $this->assertSame(40, $updated->current_page);
    }

    public function test_it_filters_the_library_by_status(): void
    {
        $user = User::factory()->create();
        LibraryItem::factory()->for($user)->create();
        LibraryItem::factory()->for($user)->reading()->create();

        $reading = app(ListLibrary::class)->handle($user, LibraryStatus::Reading);

        $this->assertCount(1, $reading);
        $this->assertSame(LibraryStatus::Reading, $reading->first()?->status);
    }
}
