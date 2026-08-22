<?php

namespace App\Domain\Books\Exceptions;

use RuntimeException;

final class InvalidLibraryPage extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('That page number is not valid for this book.');
    }
}
