<?php

namespace App\Telegram\Data;

use App\Domain\Identity\Data\TelegramProfile;

final readonly class IncomingTelegramCallback
{
    public function __construct(
        public string $id,
        public int $chatId,
        public TelegramProfile $from,
        public string $data,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function tryFromWebhook(array $payload): ?self
    {
        $callback = $payload['callback_query'] ?? null;

        if (! is_array($callback)) {
            return null;
        }

        $id = $callback['id'] ?? null;
        $data = $callback['data'] ?? null;
        $from = $callback['from'] ?? null;
        $message = $callback['message'] ?? null;
        $chat = is_array($message) ? ($message['chat'] ?? null) : null;

        if (! is_string($id) || $id === '' || ! is_string($data) || $data === '') {
            return null;
        }

        if (! is_array($from) || ! isset($from['id']) || ! is_numeric($from['id'])) {
            return null;
        }

        $chatId = is_array($chat) && isset($chat['id']) && is_numeric($chat['id'])
            ? (int) $chat['id']
            : (int) $from['id'];

        return new self(
            id: $id,
            chatId: $chatId,
            from: new TelegramProfile(
                telegramId: (int) $from['id'],
                username: self::optionalString($from['username'] ?? null),
                firstName: self::optionalString($from['first_name'] ?? null),
                lastName: self::optionalString($from['last_name'] ?? null),
                languageCode: self::optionalString($from['language_code'] ?? null),
            ),
            data: $data,
        );
    }

    public function addVolumeId(): ?string
    {
        if (! str_starts_with($this->data, 'add:')) {
            return null;
        }

        $volumeId = substr($this->data, 4);

        return $volumeId === '' ? null : $volumeId;
    }

    public function libraryFilter(): ?string
    {
        if (! preg_match('/^lib:f:(all|want|reading|finished)$/', $this->data, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @return array{id: int, status: string}|null
     */
    public function libraryStatusChange(): ?array
    {
        if (! preg_match('/^lib:s:(\d+):(want|reading|finished)$/', $this->data, $matches)) {
            return null;
        }

        return [
            'id' => (int) $matches[1],
            'status' => $matches[2],
        ];
    }

    /**
     * @return array{action: string, id: int}|null
     */
    public function readAction(): ?array
    {
        if (! preg_match('/^read:(start|stop|cancel|pause|resume):(\d+)$/', $this->data, $matches)) {
            return null;
        }

        return [
            'action' => $matches[1],
            'id' => (int) $matches[2],
        ];
    }

    private static function optionalString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
