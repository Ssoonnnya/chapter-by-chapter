<?php

namespace Tests\Feature\Library;

use App\Domain\Books\Actions\CancelReadingSession;
use App\Domain\Books\Actions\ListReadingSessions;
use App\Domain\Books\Actions\PauseReadingSession;
use App\Domain\Books\Actions\ResumeReadingSession;
use App\Domain\Books\Actions\StartReadingSession;
use App\Domain\Books\Actions\StopReadingSession;
use App\Domain\Books\Exceptions\BookNotReading;
use App\Domain\Books\Exceptions\InvalidLibraryPage;
use App\Domain\Books\Exceptions\LibraryItemNotFound;
use App\Domain\Books\Exceptions\NoOpenSession;
use App\Domain\Books\Exceptions\SessionAlreadyOpen;
use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_starts_only_on_an_owned_reading_book(): void
    {
        $user = User::factory()->create();
        $want = LibraryItem::factory()->for($user)->create();
        $reading = LibraryItem::factory()->for($user)->reading()->create(['current_page' => 12]);

        try {
            app(StartReadingSession::class)->handle($user, $want->id, 0);
            $this->fail('Sessions must not start on want-to-read books.');
        } catch (BookNotReading) {
            $this->assertDatabaseCount('reading_sessions', 0);
        }

        $other = User::factory()->create();

        try {
            app(StartReadingSession::class)->handle($other, $reading->id, 12);
            $this->fail('Sessions must be scoped to the owner.');
        } catch (LibraryItemNotFound) {
            $this->assertDatabaseCount('reading_sessions', 0);
        }

        $session = app(StartReadingSession::class)->handle($user, $reading->id, 12);

        $this->assertTrue($session->isOpen());
        $this->assertSame(12, $session->start_page);
        $this->assertSame($reading->id, $session->library_item_id);
    }

    public function test_a_second_start_is_rejected_while_one_is_open(): void
    {
        $user = User::factory()->create();
        $first = LibraryItem::factory()->for($user)->reading()->create();
        $second = LibraryItem::factory()->for($user)->reading()->create();

        app(StartReadingSession::class)->handle($user, $first->id, 0);

        try {
            app(StartReadingSession::class)->handle($user, $second->id, 0);
            $this->fail('Only one open session is allowed.');
        } catch (SessionAlreadyOpen) {
            $this->assertSame(1, ReadingSession::query()->open()->count());
        }
    }

    public function test_stop_updates_the_current_page_and_closes_the_session(): void
    {
        $this->freezeTime();

        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->for(
            Book::factory()->create(['page_count' => 300]),
        )->reading()->create(['current_page' => 10]);

        app(StartReadingSession::class)->handle($user, $item->id, 10);
        $this->travel(20)->minutes();

        $session = app(StopReadingSession::class)->handle($user, 25, $item->id);

        $this->assertFalse($session->isOpen());
        $this->assertSame(25, $session->end_page);
        $this->assertSame(15, $session->pagesRead());
        $this->assertSame(20 * 60, $session->durationSeconds());
        $this->assertSame(25, $item->fresh()?->current_page);
    }

    public function test_it_rejects_an_end_page_past_the_book_length(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->for(
            Book::factory()->create(['page_count' => 40]),
        )->reading()->create();

        app(StartReadingSession::class)->handle($user, $item->id, 0);

        try {
            app(StopReadingSession::class)->handle($user, 41, $item->id);
            $this->fail('Pages past the end of the book should be rejected.');
        } catch (InvalidLibraryPage) {
            $this->assertTrue(ReadingSession::openFor($user)?->isOpen() ?? false);
            $this->assertSame(0, $item->fresh()?->current_page);
        }
    }

    public function test_it_rejects_an_end_page_before_the_start_page(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->reading()->create(['current_page' => 20]);

        app(StartReadingSession::class)->handle($user, $item->id, 20);

        $this->expectException(InvalidLibraryPage::class);
        app(StopReadingSession::class)->handle($user, 5, $item->id);
    }

    public function test_cancel_does_not_change_the_page(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->reading()->create(['current_page' => 8]);

        app(StartReadingSession::class)->handle($user, $item->id, 8);
        $session = app(CancelReadingSession::class)->handle($user, $item->id);

        $this->assertFalse($session->isOpen());
        $this->assertNull($session->end_page);
        $this->assertNull($session->pagesRead());
        $this->assertSame(8, $item->fresh()?->current_page);
    }

    public function test_pause_freezes_duration_and_resume_continues(): void
    {
        $this->freezeTime();

        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->reading()->create();

        app(StartReadingSession::class)->handle($user, $item->id, 3);
        $this->travel(10)->minutes();
        app(PauseReadingSession::class)->handle($user, $item->id);
        $this->travel(30)->minutes();
        app(ResumeReadingSession::class)->handle($user, $item->id);
        $this->travel(5)->minutes();

        $session = app(StopReadingSession::class)->handle($user, 8, $item->id);

        $this->assertSame(15 * 60, $session->durationSeconds());
        $this->assertSame(8, $item->fresh()?->current_page);
        $this->assertFalse($session->isPaused());
    }

    public function test_stop_without_an_open_session_is_rejected(): void
    {
        $this->expectException(NoOpenSession::class);

        app(StopReadingSession::class)->handle(User::factory()->create(), 10);
    }

    public function test_it_lists_sessions_for_the_owner(): void
    {
        $user = User::factory()->create();
        $item = LibraryItem::factory()->for($user)->reading()->create();
        $other = LibraryItem::factory()->reading()->create();

        ReadingSession::factory()->for($user)->for($item, 'libraryItem')->stopped(12)->create();
        ReadingSession::factory()->for($other->user)->for($other, 'libraryItem')->stopped(3)->create();

        $all = app(ListReadingSessions::class)->handle($user);
        $filtered = app(ListReadingSessions::class)->handle($user, $item->id);

        $this->assertCount(1, $all);
        $this->assertCount(1, $filtered);
        $this->assertSame($item->id, $all->first()?->library_item_id);
    }
}
