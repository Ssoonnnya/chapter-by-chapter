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
}
