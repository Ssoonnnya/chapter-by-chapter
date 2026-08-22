<?php

namespace App\Domain\Books\Exceptions;

use RuntimeException;

final class LibraryItemNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This library item was not found.');
    }
}
