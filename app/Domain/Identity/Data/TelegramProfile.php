<?php

namespace App\Domain\Identity\Data;

final readonly class TelegramProfile
{
    public function __construct(
        public int $telegramId,
        public ?string $username,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $languageCode,
    ) {}
}
