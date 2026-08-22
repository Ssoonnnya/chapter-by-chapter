<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Data\TelegramLinkTokenResult;
use App\Domain\Identity\Exceptions\TelegramBotUsernameMissing;
use App\Domain\Identity\Exceptions\UserAlreadyHasTelegram;
use App\Domain\Identity\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateTelegramLinkToken
{
    public function handle(User $user): TelegramLinkTokenResult
    {
        if ($user->telegramIdentity !== null) {
            throw new UserAlreadyHasTelegram;
        }

        $username = $this->botUsername();
        $ttlMinutes = (int) config('telegram.link_token_ttl_minutes');
        $plainToken = Str::random(48);
        $expiresAt = now()->addMinutes($ttlMinutes);

        DB::transaction(function () use ($user, $plainToken, $expiresAt): void {
            TelegramLinkToken::query()
                ->where('user_id', $user->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            TelegramLinkToken::query()->create([
                'user_id' => $user->id,
                'token_hash' => TelegramLinkToken::hash($plainToken),
                'expires_at' => $expiresAt,
            ]);
        });

        return new TelegramLinkTokenResult(
            url: 'https://t.me/'.$username.'?start='.$plainToken,
            expiresAt: $expiresAt,
        );
    }

    private function botUsername(): string
    {
        $username = config('telegram.bot_username');

        if (! is_string($username) || $username === '') {
            throw new TelegramBotUsernameMissing;
        }

        return ltrim($username, '@');
    }
}
