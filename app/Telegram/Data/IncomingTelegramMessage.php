<?php

namespace App\Telegram\Data;

use App\Domain\Identity\Data\TelegramProfile;

final readonly class IncomingTelegramMessage
{
    public function __construct(
        public int $chatId,
        public TelegramProfile $from,
        public string $text,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function tryFromWebhook(array $payload): ?self
    {
        $message = $payload['message'] ?? null;

        if (! is_array($message)) {
            return null;
        }

        $from = $message['from'] ?? null;
        $chat = $message['chat'] ?? null;
        $text = $message['text'] ?? null;

        if (! is_array($from) || ! isset($from['id']) || ! is_numeric($from['id'])) {
            return null;
        }

        if (! is_array($chat) || ! isset($chat['id']) || ! is_numeric($chat['id'])) {
            return null;
        }

        if (! is_string($text) || $text === '') {
            return null;
        }

        return new self(
            chatId: (int) $chat['id'],
            from: new TelegramProfile(
                telegramId: (int) $from['id'],
                username: self::optionalString($from['username'] ?? null),
                firstName: self::optionalString($from['first_name'] ?? null),
                lastName: self::optionalString($from['last_name'] ?? null),
                languageCode: self::optionalString($from['language_code'] ?? null),
            ),
            text: $text,
        );
    }

    public function isStartCommand(): bool
    {
        return (bool) preg_match('/^\/start(?:@[A-Za-z0-9_]+)?(?:\s|$)/u', $this->text);
    }

    public function startToken(): ?string
    {
        return $this->commandArgument('start');
    }

    public function isSearchCommand(): bool
    {
        return $this->isCommand('search');
    }

    public function searchQuery(): ?string
    {
        return $this->commandArgument('search');
    }

    public function isLibraryCommand(): bool
    {
        return $this->isCommand('library');
    }

    public function libraryFilter(): ?string
    {
        return $this->commandArgument('library');
    }

    public function isReadCommand(): bool
    {
        return $this->isCommand('read');
    }

    public function barePageNumber(): ?int
    {
        if (! preg_match('/^\d+$/u', trim($this->text))) {
            return null;
        }

        return (int) $this->text;
    }

    private function isCommand(string $name): bool
    {
        return (bool) preg_match('/^\/'.preg_quote($name, '/').'(?:@[A-Za-z0-9_]+)?(?:\s|$)/u', $this->text);
    }

    private function commandArgument(string $name): ?string
    {
        if (! preg_match('/^\/'.preg_quote($name, '/').'(?:@[A-Za-z0-9_]+)?(?:\s+(.+))?$/u', $this->text, $matches)) {
            return null;
        }

        $argument = isset($matches[1]) ? trim($matches[1]) : '';

        return $argument === '' ? null : $argument;
    }

    private static function optionalString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
