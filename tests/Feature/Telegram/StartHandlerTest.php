<?php

namespace Tests\Feature\Telegram;

use App\Domain\Identity\Actions\GenerateTelegramLinkToken;
use App\Models\User;
use App\Telegram\BotReplies;
use App\Telegram\Contracts\TelegramMessenger;
use App\Telegram\Testing\FakeTelegramMessenger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTelegram;
use Tests\TestCase;

class StartHandlerTest extends TestCase
{
    use InteractsWithTelegram, RefreshDatabase;

    public function test_start_without_payload_and_without_identity_sends_guest_reply(): void
    {
        $this->postJson(route('telegram.webhook'), $this->telegramStartUpdate('/start'), $this->webhookHeaders())
            ->assertOk();

        $this->assertSame([BotReplies::guest()], $this->messenger()->texts());
        $this->assertDatabaseCount('telegram_identities', 0);
    }

    public function test_start_without_payload_welcomes_a_linked_user(): void
    {
        $user = User::factory()->withTelegram(111222333)->create(['name' => 'Olena']);

        $this->postJson(route('telegram.webhook'), $this->telegramStartUpdate('/start', 111222333), $this->webhookHeaders())
            ->assertOk();

        $this->assertSame([BotReplies::welcome($user->name)], $this->messenger()->texts());
    }

    public function test_start_with_a_valid_token_links_the_account(): void
    {
        $user = User::factory()->create(['name' => 'Mykhailo']);
        $token = $this->startTokenFromResult(app(GenerateTelegramLinkToken::class)->handle($user));

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('/start '.$token, 888777666, ['username' => 'mykhailo', 'language_code' => 'uk']),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertSame([BotReplies::linked('Mykhailo')], $this->messenger()->texts());
        $this->assertDatabaseHas('telegram_identities', [
            'user_id' => $user->id,
            'telegram_id' => 888777666,
            'username' => 'mykhailo',
        ]);
    }

    public function test_start_with_an_invalid_token_does_not_create_an_identity(): void
    {
        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('/start not-a-real-token'),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertSame([BotReplies::invalidToken()], $this->messenger()->texts());
        $this->assertDatabaseCount('telegram_identities', 0);
    }

    public function test_start_rejects_a_telegram_id_already_linked_to_another_user(): void
    {
        User::factory()->withTelegram(100)->create();
        $user = User::factory()->create();
        $token = $this->startTokenFromResult(app(GenerateTelegramLinkToken::class)->handle($user));

        $this->postJson(
            route('telegram.webhook'),
            $this->telegramStartUpdate('/start '.$token, 100),
            $this->webhookHeaders(),
        )->assertOk();

        $this->assertSame([BotReplies::telegramAlreadyLinked()], $this->messenger()->texts());
        $this->assertDatabaseMissing('telegram_identities', ['user_id' => $user->id]);
    }

    protected function messenger(): FakeTelegramMessenger
    {
        $messenger = app(TelegramMessenger::class);
        $this->assertInstanceOf(FakeTelegramMessenger::class, $messenger);

        return $messenger;
    }
}
