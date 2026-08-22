<?php

namespace App\Domain\Books\Enums;

enum LibraryStatus: string
{
    case Want = 'want';
    case Reading = 'reading';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Want => 'Want to read',
            self::Reading => 'Reading',
            self::Finished => 'Finished',
        };
    }
}
