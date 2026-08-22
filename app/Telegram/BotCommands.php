<?php

namespace App\Telegram;

final class BotCommands
{
    /**
     * @return list<array{command: string, description: string}>
     */
    public static function definitions(): array
    {
        return [
            ['command' => 'start', 'description' => 'Connect your account'],
            ['command' => 'search', 'description' => 'Find a book by title'],
            ['command' => 'library', 'description' => 'Your books'],
        ];
    }
}
