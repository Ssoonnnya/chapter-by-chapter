<?php

namespace App\Telegram;

use Illuminate\Support\Facades\Cache;

final class PendingSessionPage
{
    /**
     * @param  'start'|'stop'  $action
     */
    public function remember(int $telegramId, string $action, int $libraryItemId): void
    {
        Cache::put($this->key($telegramId), $action.':'.$libraryItemId, now()->addMinutes(30));
    }

    /**
     * @return array{action: string, library_item_id: int}|null
     */
    public function peek(int $telegramId): ?array
    {
        return $this->parse(Cache::get($this->key($telegramId)));
    }

    public function forget(int $telegramId): void
    {
        Cache::forget($this->key($telegramId));
    }

    /**
     * @return array{action: string, library_item_id: int}|null
     */
    private function parse(mixed $value): ?array
    {
        if (! is_string($value) || ! preg_match('/^(start|stop):(\d+)$/', $value, $matches)) {
            return null;
        }

        return [
            'action' => $matches[1],
            'library_item_id' => (int) $matches[2],
        ];
    }

    private function key(int $telegramId): string
    {
        return 'telegram:session-page:'.$telegramId;
    }
}
