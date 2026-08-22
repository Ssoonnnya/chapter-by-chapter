<?php

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

final class TelegramAlreadyLinked extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This Telegram account is already connected to another user.');
    }
}
