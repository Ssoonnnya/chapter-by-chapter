<?php

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

final class TelegramBotUsernameMissing extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Telegram bot username is not configured.');
    }
}
