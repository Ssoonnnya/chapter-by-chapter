<?php

namespace App\Domain\Identity\Data;

use Carbon\CarbonInterface;

final readonly class TelegramLinkTokenResult
{
    public function __construct(
        public string $url,
        public CarbonInterface $expiresAt,
    ) {}
}
