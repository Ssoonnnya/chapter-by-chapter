<?php

namespace Tests\Unit\Telegram;

use App\Telegram\Data\IncomingTelegramMessage;
use PHPUnit\Framework\TestCase;

class IncomingTelegramMessageTest extends TestCase
{
    public function test_it_extracts_a_start_token(): void
    {
        $message = IncomingTelegramMessage::tryFromWebhook([
            'message' => [
                'chat' => ['id' => 10],
                'from' => ['id' => 10, 'username' => 'ada'],
                'text' => '/start abc123',
            ],
        ]);

        $this->assertNotNull($message);
        $this->assertTrue($message->isStartCommand());
        $this->assertSame('abc123', $message->startToken());
    }

    public function test_start_without_payload_has_no_token(): void
    {
        $message = IncomingTelegramMessage::tryFromWebhook([
            'message' => [
                'chat' => ['id' => 10],
                'from' => ['id' => 10],
                'text' => '/start',
            ],
        ]);

        $this->assertNotNull($message);
        $this->assertTrue($message->isStartCommand());
        $this->assertNull($message->startToken());
    }

    public function test_it_ignores_non_start_messages(): void
    {
        $message = IncomingTelegramMessage::tryFromWebhook([
            'message' => [
                'chat' => ['id' => 10],
                'from' => ['id' => 10],
                'text' => 'hello',
            ],
        ]);

        $this->assertNotNull($message);
        $this->assertFalse($message->isStartCommand());
        $this->assertNull($message->startToken());
    }

    public function test_it_parses_search_and_library_commands(): void
    {
        $search = IncomingTelegramMessage::tryFromWebhook([
            'message' => [
                'chat' => ['id' => 10],
                'from' => ['id' => 10],
                'text' => '/search Лісова пісня',
            ],
        ]);

        $this->assertNotNull($search);
        $this->assertTrue($search->isSearchCommand());
        $this->assertSame('Лісова пісня', $search->searchQuery());

        $library = IncomingTelegramMessage::tryFromWebhook([
            'message' => [
                'chat' => ['id' => 10],
                'from' => ['id' => 10],
                'text' => '/library',
            ],
        ]);

        $this->assertNotNull($library);
        $this->assertTrue($library->isLibraryCommand());
        $this->assertNull($library->libraryFilter());

        $filtered = IncomingTelegramMessage::tryFromWebhook([
            'message' => [
                'chat' => ['id' => 10],
                'from' => ['id' => 10],
                'text' => '/library reading',
            ],
        ]);

        $this->assertSame('reading', $filtered?->libraryFilter());
    }

    public function test_it_parses_read_and_a_bare_page_number(): void
    {
        $read = IncomingTelegramMessage::tryFromWebhook([
            'message' => [
                'chat' => ['id' => 10],
                'from' => ['id' => 10],
                'text' => '/read',
            ],
        ]);

        $this->assertNotNull($read);
        $this->assertTrue($read->isReadCommand());
        $this->assertNull($read->barePageNumber());

        $page = IncomingTelegramMessage::tryFromWebhook([
            'message' => [
                'chat' => ['id' => 10],
                'from' => ['id' => 10],
                'text' => '42',
            ],
        ]);

        $this->assertSame(42, $page?->barePageNumber());
    }

    public function test_it_parses_stats(): void
    {
        $stats = IncomingTelegramMessage::tryFromWebhook([
            'message' => [
                'chat' => ['id' => 10],
                'from' => ['id' => 10],
                'text' => '/stats',
            ],
        ]);

        $this->assertNotNull($stats);
        $this->assertTrue($stats->isStatsCommand());
        $this->assertFalse($stats->isReadCommand());
    }
}
