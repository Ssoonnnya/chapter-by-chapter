<?php

namespace App\Domain\Books\Language;

final class BlockedBookLanguages
{
    /**
     * @var list<string>
     */
    private const BLOCKED = ['ru', 'rus'];

    public static function contains(?string $language): bool
    {
        if ($language === null || $language === '') {
            return false;
        }

        return in_array(strtolower($language), self::BLOCKED, true);
    }
}
