<?php

namespace Tests\Unit\Books;

use App\Domain\Books\Language\BlockedBookLanguages;
use PHPUnit\Framework\TestCase;

class BlockedBookLanguagesTest extends TestCase
{
    public function test_it_blocks_russian_language_codes(): void
    {
        $this->assertTrue(BlockedBookLanguages::contains('ru'));
        $this->assertTrue(BlockedBookLanguages::contains('rus'));
        $this->assertTrue(BlockedBookLanguages::contains('RU'));
    }

    public function test_it_allows_other_languages_and_missing_codes(): void
    {
        $this->assertFalse(BlockedBookLanguages::contains('en'));
        $this->assertFalse(BlockedBookLanguages::contains('uk'));
        $this->assertFalse(BlockedBookLanguages::contains('fr'));
        $this->assertFalse(BlockedBookLanguages::contains(null));
        $this->assertFalse(BlockedBookLanguages::contains(''));
    }
}
