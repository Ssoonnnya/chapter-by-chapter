<?php

namespace Tests\Feature\Telegram;

use App\Domain\Identity\Actions\ResolveUserByTelegramId;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveUserByTelegramIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_user_for_a_linked_telegram_id(): void
    {
        $user = User::factory()->withTelegram(424242)->create();

        $resolved = app(ResolveUserByTelegramId::class)->handle(424242);

        $this->assertTrue($user->is($resolved));
    }

    public function test_it_returns_null_when_telegram_id_is_unknown(): void
    {
        $this->assertNull(app(ResolveUserByTelegramId::class)->handle(1));
    }
}
