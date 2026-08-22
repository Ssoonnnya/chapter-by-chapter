<?php

namespace Tests\Feature\Telegram;

use App\Domain\Books\Enums\LibraryStatus;
use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\LibraryItem;
use App\Domain\Books\Models\ReadingSession;
use App\Domain\Books\Testing\FakeBookProvider;
use App\Models\User;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Testing\FakeTelegramMessenger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBookCards;
use Tests\Concerns\InteractsWithTelegram;
use Tests\TestCase;

class BookBotTest extends TestCase
{
    use BuildsBookCards, InteractsWithTelegram, RefreshDatabase;

    public function test_search_without_identity_is_guest(): void
    {
        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('/search hobbit'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertSame([BotReplies::guest()], $this->messenger()->texts());
    }

    public function test_search_without_a_query_shows_usage(): void
    {
        User::factory()->withTelegram(111222333)->create();

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('/search'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertSame([BotReplies::searchUsage()], $this->messenger()->texts());
    }

    public function test_linked_user_can_search_in_any_language(): void
    {
        User::factory()->withTelegram(111222333)->create();
        app(FakeBookProvider::class)->add($this->bookCard([
            'title' => 'Лісова пісня',
            'language' => 'uk',
        ]));

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('/search пісня'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertStringContainsString('Лісова пісня', $this->messenger()->texts()[0]);
        $this->assertNotEmpty($this->messenger()->messages[0]['reply_markup']);
    }

    public function test_add_callback_creates_a_library_item(): void
    {
        $user = User::factory()->withTelegram(111222333)->create();
        app(FakeBookProvider::class)->add($this->bookCard());

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramCallbackUpdate('add:vol-hobbit'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertSame([BotReplies::added('The Hobbit')], $this->messenger()->texts());
        $this->assertDatabaseHas('library_items', ['user_id' => $user->id]);
    }

    public function test_library_lists_added_titles(): void
    {
        $user = User::factory()->withTelegram(111222333)->create();
        LibraryItem::factory()->for($user)->for(Book::factory()->create(['title' => 'The Hobbit']))->create();

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('/library'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertStringContainsString('The Hobbit', $this->messenger()->texts()[0]);
        $this->assertStringContainsString('Want to read', $this->messenger()->texts()[0]);
    }

    public function test_library_status_callback_updates_the_shelf(): void
    {
        $user = User::factory()->withTelegram(111222333)->create();
        $item = LibraryItem::factory()->for($user)->for(Book::factory()->create(['title' => 'The Hobbit']))->create();

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramCallbackUpdate('lib:s:'.$item->id.':reading'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertSame(LibraryStatus::Reading, $item->fresh()?->status);
        $this->assertStringContainsString('Reading', implode("\n", $this->messenger()->texts()));
    }

    public function test_library_filter_callback_lists_only_matching_books(): void
    {
        $user = User::factory()->withTelegram(111222333)->create();
        LibraryItem::factory()->for($user)->for(Book::factory()->create(['title' => 'Wanted']))->create();
        LibraryItem::factory()->for($user)->reading()->for(Book::factory()->create(['title' => 'Current']))->create();

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramCallbackUpdate('lib:f:reading'),
            $this->webhookHeaders(),
        )->assertOk();

        $text = $this->messenger()->texts()[0];
        $this->assertStringContainsString('Current', $text);
        $this->assertStringNotContainsString('Wanted', $text);
    }

    public function test_read_without_identity_is_guest(): void
    {
        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('/read'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertSame([BotReplies::guest()], $this->messenger()->texts());
    }

    public function test_read_lists_reading_books(): void
    {
        $user = User::factory()->withTelegram(111222333)->create();
        LibraryItem::factory()->for($user)->for(Book::factory()->create(['title' => 'The Hobbit']))->reading()->create();

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('/read'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertStringContainsString('The Hobbit', $this->messenger()->texts()[0]);
    }

    public function test_read_start_and_stop_with_a_page(): void
    {
        $user = User::factory()->withTelegram(111222333)->create();
        $item = LibraryItem::factory()->for($user)->for(
            Book::factory()->create(['title' => 'The Hobbit', 'page_count' => 300]),
        )->reading()->create(['current_page' => 10]);

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramCallbackUpdate('read:start:'.$item->id),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertNull(ReadingSession::openFor($user));
        $this->assertStringContainsString('starting from', implode("\n", $this->messenger()->texts()));

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('10'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertNotNull(ReadingSession::openFor($user));
        $this->assertStringContainsString('Started reading', implode("\n", $this->messenger()->texts()));

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramCallbackUpdate('read:stop:'.$item->id),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertStringContainsString('What page', implode("\n", $this->messenger()->texts()));

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('22'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertSame(22, $item->fresh()?->current_page);
        $this->assertNull(ReadingSession::openFor($user));
        $this->assertStringContainsString('page 22', implode("\n", $this->messenger()->texts()));
    }

    public function test_read_cancel_leaves_the_page_unchanged(): void
    {
        $user = User::factory()->withTelegram(111222333)->create();
        $item = LibraryItem::factory()->for($user)->for(Book::factory()->create(['title' => 'The Hobbit']))->reading()->create(['current_page' => 7]);

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramCallbackUpdate('read:start:'.$item->id),
            $this->webhookHeaders(),
        )->assertOk();

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('7'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramCallbackUpdate('read:cancel:'.$item->id),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertSame(7, $item->fresh()?->current_page);
        $this->assertNull(ReadingSession::openFor($user));
    }

    protected function messenger(): FakeTelegramMessenger
    {
        $messenger = app(TelegramMessenger::class);
        $this->assertInstanceOf(FakeTelegramMessenger::class, $messenger);

        return $messenger;
    }
}
