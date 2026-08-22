<?php

namespace App\Domain\Books\Data;

use App\Domain\Books\Models\Book;

final readonly class AddBookResult
{
    public function __construct(
        public Book $book,
        public bool $alreadyInLibrary,
    ) {}
}
