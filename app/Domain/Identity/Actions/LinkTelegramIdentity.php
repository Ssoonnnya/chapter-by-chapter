<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Data\TelegramProfile;
use App\Domain\Identity\Exceptions\TelegramAlreadyLinked;
use App\Domain\Identity\Exceptions\TelegramLinkTokenInvalid;
use App\Domain\Identity\Exceptions\UserAlreadyHasTelegram;
use App\Domain\Identity\Models\TelegramIdentity;
use App\Domain\Identity\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LinkTelegramIdentity
{
    public function handle(string $plainToken, TelegramProfile $profile): User
    {
        return DB::transaction(function () use ($plainToken, $profile): User {
            $token = TelegramLinkToken::query()
                ->where('token_hash', TelegramLinkToken::hash($plainToken))
                ->lockForUpdate()
                ->first();

            if ($token === null || ! $token->isUsable()) {
                throw new TelegramLinkTokenInvalid;
            }

            $user = User::query()->lockForUpdate()->findOrFail($token->user_id);

            if ($user->telegramIdentity !== null) {
                throw new UserAlreadyHasTelegram;
            }

            $existing = TelegramIdentity::query()
                ->where('telegram_id', $profile->telegramId)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                throw new TelegramAlreadyLinked;
            }

            $user->telegramIdentity()->create([
                'telegram_id' => $profile->telegramId,
                'username' => $profile->username,
                'first_name' => $profile->firstName,
                'last_name' => $profile->lastName,
                'language_code' => $profile->languageCode,
                'linked_at' => now(),
            ]);

            $token->update(['used_at' => now()]);

            return $user->refresh();
        });
    }
}
