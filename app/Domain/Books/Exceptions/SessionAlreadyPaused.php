<?php

namespace App\Domain\Books\Exceptions;

use RuntimeException;

final class SessionAlreadyPaused extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This reading session is already paused.');
    }
}
