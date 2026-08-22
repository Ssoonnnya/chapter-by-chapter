<?php

namespace App\Domain\Books\Contracts;

use App\Domain\Books\Data\BookCard;

interface BookProvider
{
    /**
     * @return list<BookCard>
     */
    public function search(string $query, int $limit): array;

    public function find(string $googleVolumeId): ?BookCard;
}
