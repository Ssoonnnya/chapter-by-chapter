<?php

namespace App\Domain\Books\Exceptions;

use RuntimeException;

final class NoOpenSession extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('There is no open reading session.');
    }
}
