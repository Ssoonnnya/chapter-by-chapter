<?php

namespace Tests\Feature\Telegram;

use App\Domain\Identity\Actions\GenerateTelegramLinkToken;
use App\Domain\Identity\Exceptions\TelegramBotUsernameMissing;
use App\Domain\Identity\Exceptions\UserAlreadyHasTelegram;
use App\Domain\Identity\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTelegram;
use Tests\TestCase;

class GenerateTelegramLinkTokenTest extends TestCase
{
    use InteractsWithTelegram, RefreshDatabase;

    public function test_it_stores_only_the_token_hash(): void
    {
        $this->freezeTime();

        $user = User::factory()->create();

        $result = app(GenerateTelegramLinkToken::class)->handle($user);
        $plainToken = $this->startTokenFromResult($result);

        $this->assertStringContainsString('https://t.me/chapter_bot?start=', $result->url);
        $this->assertTrue($result->expiresAt->equalTo(now()->addMinutes(10)));
        $this->assertDatabaseHas('telegram_link_tokens', [
            'user_id' => $user->id,
            'token_hash' => TelegramLinkToken::hash($plainToken),
        ]);
        $this->assertDatabaseMissing('telegram_link_tokens', [
            'token_hash' => $plainToken,
        ]);
    }

    public function test_it_invalidates_previous_unused_tokens(): void
    {
        $user = User::factory()->create();
        $generate = app(GenerateTelegramLinkToken::class);

        $first = $this->startTokenFromResult($generate->handle($user));
        $second = $this->startTokenFromResult($generate->handle($user->fresh()));

        $this->assertNotSame($first, $second);
        $this->assertNotNull(
            TelegramLinkToken::query()->where('token_hash', TelegramLinkToken::hash($first))->value('used_at'),
        );
        $this->assertNull(
            TelegramLinkToken::query()->where('token_hash', TelegramLinkToken::hash($second))->value('used_at'),
        );
    }

    public function test_it_rejects_users_who_already_have_telegram(): void
    {
        $user = User::factory()->withTelegram()->create();

        $this->expectException(UserAlreadyHasTelegram::class);

        app(GenerateTelegramLinkToken::class)->handle($user);
    }

    public function test_it_requires_a_bot_username(): void
    {
        config(['telegram.bot_username' => null]);

        $this->expectException(TelegramBotUsernameMissing::class);

        app(GenerateTelegramLinkToken::class)->handle(User::factory()->create());
    }
}
