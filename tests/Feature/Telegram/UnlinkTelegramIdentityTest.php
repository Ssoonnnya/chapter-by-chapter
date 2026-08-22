<?php

namespace Tests\Feature\Telegram;

use App\Domain\Identity\Actions\GenerateTelegramLinkToken;
use App\Domain\Identity\Actions\UnlinkTelegramIdentity;
use App\Domain\Identity\Models\TelegramIdentity;
use App\Domain\Identity\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnlinkTelegramIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_the_identity_and_invalidates_pending_tokens(): void
    {
        $user = User::factory()->create();
        app(GenerateTelegramLinkToken::class)->handle($user);

        $user->telegramIdentity()->create([
            'telegram_id' => 777,
            'linked_at' => now(),
        ]);

        app(UnlinkTelegramIdentity::class)->handle($user->fresh());

        $this->assertDatabaseMissing('telegram_identities', ['user_id' => $user->id]);
        $this->assertSame(0, TelegramIdentity::query()->where('user_id', $user->id)->count());
        $this->assertSame(
            0,
            TelegramLinkToken::query()->where('user_id', $user->id)->whereNull('used_at')->count(),
        );
    }

    public function test_it_is_safe_when_the_user_has_no_identity(): void
    {
        $user = User::factory()->create();

        app(UnlinkTelegramIdentity::class)->handle($user);

        $this->assertDatabaseCount('telegram_identities', 0);
    }
}
