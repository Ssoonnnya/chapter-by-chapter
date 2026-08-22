<?php

namespace Tests\Unit\Enums;

use App\Domain\Identity\Enums\Locale;
use PHPUnit\Framework\TestCase;

class LocaleTest extends TestCase
{
    public function test_supported_locales_are_only_english_and_ukrainian(): void
    {
        $this->assertSame(['en', 'uk'], array_column(Locale::cases(), 'value'));
    }

    public function test_russian_is_not_a_locale(): void
    {
        $this->assertNull(Locale::tryFrom('ru'));
    }

    public function test_english_and_ukrainian_can_be_resolved(): void
    {
        $this->assertSame(Locale::En, Locale::tryFrom('en'));
        $this->assertSame(Locale::Uk, Locale::tryFrom('uk'));
    }
}
