<?php

namespace Tests\Feature\Settings;

use App\Domain\Identity\Actions\GenerateTelegramLinkToken;
use App\Domain\Identity\Models\TelegramIdentity;
use App\Domain\Identity\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\InteractsWithTelegram;
use Tests\TestCase;

class TelegramLinkTest extends TestCase
{
    use InteractsWithTelegram, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guests_cannot_view_telegram_settings(): void
    {
        $this->get(route('telegram.edit'))->assertRedirect(route('login'));
    }

    public function test_guests_cannot_generate_a_link(): void
    {
        $this->post(route('telegram.store'))->assertRedirect(route('login'));
    }

    public function test_telegram_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('telegram.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Telegram')
                ->where('identity', null),
            );
    }

    public function test_authenticated_users_can_generate_a_deep_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('telegram.store'))
            ->assertRedirect(route('telegram.edit'));

        $this->actingAs($user)
            ->get(route('telegram.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Telegram')
                ->where('linkUrl', fn (mixed $url) => is_string($url) && str_contains($url, 'https://t.me/chapter_bot?start='))
                ->whereNot('linkExpiresAt', null),
            );

        $this->assertDatabaseCount('telegram_link_tokens', 1);
    }

    public function test_link_generation_is_rate_limited(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('telegram.store'))->assertRedirect(route('telegram.edit'));
        }

        $this->post(route('telegram.store'))->assertTooManyRequests();
    }

    public function test_connected_identity_is_shown(): void
    {
        $user = User::factory()->withTelegram(123456)->create();
        $user->telegramIdentity?->update(['username' => 'reader']);

        $this->actingAs($user->fresh())
            ->get(route('telegram.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Telegram')
                ->where('identity.telegram_id', 123456)
                ->where('identity.username', 'reader'),
            );
    }

    public function test_user_can_unlink_their_own_telegram(): void
    {
        $user = User::factory()->withTelegram()->create();

        $this->actingAs($user)
            ->delete(route('telegram.destroy'))
            ->assertRedirect(route('telegram.edit'));

        $this->assertDatabaseMissing('telegram_identities', ['user_id' => $user->id]);
        $this->assertSame(0, TelegramIdentity::query()->count());
    }

    public function test_unlink_uses_only_the_authenticated_user(): void
    {
        $owner = User::factory()->withTelegram(1)->create();
        $other = User::factory()->withTelegram(2)->create();

        $this->actingAs($other)
            ->delete(route('telegram.destroy'))
            ->assertRedirect(route('telegram.edit'));

        $this->assertDatabaseHas('telegram_identities', ['user_id' => $owner->id, 'telegram_id' => 1]);
        $this->assertDatabaseMissing('telegram_identities', ['user_id' => $other->id]);
    }

    public function test_regenerating_from_the_website_invalidates_the_previous_token(): void
    {
        $user = User::factory()->create();
        $first = $this->startTokenFromResult(app(GenerateTelegramLinkToken::class)->handle($user));

        $this->actingAs($user)->post(route('telegram.store'));

        $this->assertNotNull(
            TelegramLinkToken::query()->where('token_hash', TelegramLinkToken::hash($first))->value('used_at'),
        );
    }
}
