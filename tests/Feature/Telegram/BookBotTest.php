<?php

namespace Tests\Feature\Telegram;

use App\Domain\Books\Models\Book;
use App\Domain\Books\Models\LibraryItem;
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
    }

    protected function messenger(): FakeTelegramMessenger
    {
        $messenger = app(TelegramMessenger::class);
        $this->assertInstanceOf(FakeTelegramMessenger::class, $messenger);

        return $messenger;
    }
}
