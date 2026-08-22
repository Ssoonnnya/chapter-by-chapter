<?php

namespace App\Domain\Books\Exceptions;

use RuntimeException;

final class UnsupportedBookLanguage extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Russian-language books are not supported.');
    }
}
