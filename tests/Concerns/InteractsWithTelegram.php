<?php

namespace Tests\Concerns;

use App\Domain\Identity\Data\TelegramLinkTokenResult;

trait InteractsWithTelegram
{
    protected function startTokenFromUrl(string $url): string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        $this->assertIsString($query);

        parse_str($query, $params);

        $this->assertIsString($params['start'] ?? null);

        return $params['start'];
    }

    protected function startTokenFromResult(TelegramLinkTokenResult $result): string
    {
        return $this->startTokenFromUrl($result->url);
    }

    /**
     * @param  array<string, mixed>  $from
     * @return array<string, mixed>
     */
    protected function telegramStartUpdate(string $text, int $telegramId = 111222333, array $from = []): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'date' => time(),
                'chat' => ['id' => $telegramId, 'type' => 'private'],
                'from' => [
                    'id' => $telegramId,
                    'is_bot' => false,
                    'first_name' => $from['first_name'] ?? 'Ada',
                    'last_name' => $from['last_name'] ?? 'Lovelace',
                    'username' => $from['username'] ?? 'ada',
                    'language_code' => $from['language_code'] ?? 'uk',
                ],
                'text' => $text,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function telegramCallbackUpdate(string $data, int $telegramId = 111222333): array
    {
        return [
            'update_id' => 2,
            'callback_query' => [
                'id' => 'callback-1',
                'from' => [
                    'id' => $telegramId,
                    'is_bot' => false,
                    'first_name' => 'Ada',
                    'username' => 'ada',
                ],
                'message' => [
                    'message_id' => 2,
                    'date' => time(),
                    'chat' => ['id' => $telegramId, 'type' => 'private'],
                    'from' => ['id' => 1, 'is_bot' => true, 'first_name' => 'Bot'],
                ],
                'data' => $data,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function webhookHeaders(): array
    {
        return [
            'X-Telegram-Bot-Api-Secret-Token' => (string) config('telegram.webhook_secret'),
        ];
    }
}
