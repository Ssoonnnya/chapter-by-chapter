<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\TelegramIdentity;
use App\Models\User;

class ResolveUserByTelegramId
{
    public function handle(int $telegramId): ?User
    {
        $identity = TelegramIdentity::query()
            ->where('telegram_id', $telegramId)
            ->first();

        return $identity?->user;
    }
}
