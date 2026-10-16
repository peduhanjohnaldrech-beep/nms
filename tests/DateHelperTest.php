<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class DateHelperTest extends TestCase
{
    public function testFormatDateReturnsFormattedDate(): void
    {
        $this->assertSame('Jan 1, 2026', \DateHelper::formatDate('2026-01-01', 'M j, Y'));
    }

    public function testFormatDateReturnsDefaultOnNull(): void
    {
        $this->assertSame('—', \DateHelper::formatDate(null));
        $this->assertSame('—', \DateHelper::formatDate(''));
    }

    public function testFormatDateReturnsDefaultOnInvalid(): void
    {
        $result = \DateHelper::formatDate('not-a-date');
        $this->assertNotEmpty($result);
    }

    public function testAgeInMonthsReturnsCorrectValue(): void
    {
        $dob = date('Y-m-d', strtotime('-24 months'));
        $age = \DateHelper::ageInMonths($dob);
        $this->assertEqualsWithDelta(24, $age, 1);
    }

    public function testAgeInMonthsForNewborn(): void
    {
        $dob = date('Y-m-d');
        $this->assertSame(0, \DateHelper::ageInMonths($dob));
    }
}
