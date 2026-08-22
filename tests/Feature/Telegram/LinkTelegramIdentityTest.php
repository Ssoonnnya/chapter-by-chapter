<?php

namespace Tests\Feature\Telegram;

use App\Domain\Identity\Actions\GenerateTelegramLinkToken;
use App\Domain\Identity\Actions\LinkTelegramIdentity;
use App\Domain\Identity\Data\TelegramProfile;
use App\Domain\Identity\Exceptions\TelegramAlreadyLinked;
use App\Domain\Identity\Exceptions\TelegramLinkTokenInvalid;
use App\Domain\Identity\Exceptions\UserAlreadyHasTelegram;
use App\Domain\Identity\Models\TelegramIdentity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTelegram;
use Tests\TestCase;

class LinkTelegramIdentityTest extends TestCase
{
    use InteractsWithTelegram, RefreshDatabase;

    public function test_it_links_a_valid_token_and_does_not_copy_russian_language_to_user_locale(): void
    {
        $user = User::factory()->create();
        $token = $this->startTokenFromResult(app(GenerateTelegramLinkToken::class)->handle($user));

        $linked = app(LinkTelegramIdentity::class)->handle($token, new TelegramProfile(
            telegramId: 999888777,
            username: 'reader',
            firstName: 'Lesia',
            lastName: 'Ukrainka',
            languageCode: 'ru',
        ));

        $this->assertTrue($user->is($linked));
        $this->assertSame('en', $linked->locale->value);
        $this->assertDatabaseHas('telegram_identities', [
            'user_id' => $user->id,
            'telegram_id' => 999888777,
            'username' => 'reader',
            'language_code' => 'ru',
        ]);
    }

    public function test_it_rejects_a_reused_token(): void
    {
        $user = User::factory()->create();
        $token = $this->startTokenFromResult(app(GenerateTelegramLinkToken::class)->handle($user));
        $profile = new TelegramProfile(555, 'once', 'A', null, 'uk');

        app(LinkTelegramIdentity::class)->handle($token, $profile);

        try {
            app(LinkTelegramIdentity::class)->handle($token, new TelegramProfile(556, 'twice', 'B', null, 'uk'));
            $this->fail('Reused token should be rejected.');
        } catch (TelegramLinkTokenInvalid) {
            $this->assertSame(1, TelegramIdentity::query()->where('user_id', $user->id)->count());
        }
    }

    public function test_it_rejects_an_expired_token(): void
    {
        $user = User::factory()->create();
        $token = $this->startTokenFromResult(app(GenerateTelegramLinkToken::class)->handle($user));

        $this->travel(11)->minutes();

        try {
            app(LinkTelegramIdentity::class)->handle($token, new TelegramProfile(1, null, 'A', null, 'en'));
            $this->fail('Expired token should be rejected.');
        } catch (TelegramLinkTokenInvalid) {
            $this->assertDatabaseCount('telegram_identities', 0);
        }
    }

    public function test_it_rejects_a_telegram_id_already_linked_to_another_user(): void
    {
        User::factory()->withTelegram(321)->create();
        $user = User::factory()->create();
        $token = $this->startTokenFromResult(app(GenerateTelegramLinkToken::class)->handle($user));

        $this->expectException(TelegramAlreadyLinked::class);

        app(LinkTelegramIdentity::class)->handle($token, new TelegramProfile(321, 'taken', 'A', null, 'en'));
    }

    public function test_it_rejects_when_the_website_user_already_has_telegram(): void
    {
        $user = User::factory()->create();
        $token = $this->startTokenFromResult(app(GenerateTelegramLinkToken::class)->handle($user));

        $user->telegramIdentity()->create([
            'telegram_id' => 654321,
            'linked_at' => now(),
        ]);

        $this->expectException(UserAlreadyHasTelegram::class);

        app(LinkTelegramIdentity::class)->handle($token, new TelegramProfile(111, 'new', 'A', null, 'en'));
    }

    public function test_it_rejects_an_unknown_token(): void
    {
        $this->expectException(TelegramLinkTokenInvalid::class);

        app(LinkTelegramIdentity::class)->handle('missing', new TelegramProfile(1, null, 'A', null, 'en'));
    }
}
