<?php

namespace App\Domain\Identity\Exceptions;

use RuntimeException;

final class UserAlreadyHasTelegram extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This account already has a Telegram connection.');
    }
}
