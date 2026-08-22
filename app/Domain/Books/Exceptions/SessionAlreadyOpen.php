<?php

namespace App\Domain\Books\Exceptions;

use RuntimeException;

final class SessionAlreadyOpen extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A reading session is already open.');
    }
}
