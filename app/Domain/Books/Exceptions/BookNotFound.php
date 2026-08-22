<?php

namespace App\Domain\Books\Exceptions;

use RuntimeException;

final class BookNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This book could not be found.');
    }
}
