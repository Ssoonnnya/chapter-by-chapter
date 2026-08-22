<?php

namespace Tests\Feature\Telegram;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTelegram;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use InteractsWithTelegram, RefreshDatabase;

    public function test_webhook_rejects_a_missing_secret(): void
    {
        $this->postJson(route('telegram.webhook'), $this->telegramStartUpdate('/start'))
            ->assertForbidden();
    }

    public function test_webhook_rejects_a_wrong_secret(): void
    {
        $this->postJson(route('telegram.webhook'), $this->telegramStartUpdate('/start'), [
            'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
        ])->assertForbidden();
    }

    public function test_webhook_accepts_a_valid_secret(): void
    {
        $this->postJson(route('telegram.webhook'), $this->telegramStartUpdate('/start'), [
            'X-Telegram-Bot-Api-Secret-Token' => (string) config('telegram.webhook_secret'),
        ])->assertOk();
    }

    public function test_webhook_ignores_non_start_updates(): void
    {
        $this->postJson(route('telegram.webhook'), $this->telegramStartUpdate('hello'), [
            'X-Telegram-Bot-Api-Secret-Token' => (string) config('telegram.webhook_secret'),
        ])->assertOk();
    }
}
