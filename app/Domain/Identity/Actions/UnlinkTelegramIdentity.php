<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UnlinkTelegramIdentity
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->telegramIdentity?->delete();

            TelegramLinkToken::query()
                ->where('user_id', $user->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);
        });
    }
}
