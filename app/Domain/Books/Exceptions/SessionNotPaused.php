<?php

namespace App\Domain\Books\Exceptions;

use RuntimeException;

final class SessionNotPaused extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This reading session is not paused.');
    }
}
