<?php

namespace App\Domain\Books\Exceptions;

use RuntimeException;

final class BookNotReading extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Start a session only on a book you are reading.');
    }
}
