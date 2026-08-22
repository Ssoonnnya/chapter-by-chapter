<?php

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

final class TelegramLinkTokenInvalid extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This Telegram link is invalid or has expired.');
    }
}
