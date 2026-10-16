<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

class ReportCacheTest extends TestCase
{
    protected function setUp(): void
    {
        \ReportCache::invalidate();
    }

    protected function tearDown(): void
    {
        \ReportCache::invalidate();
    }

    public function testKeyGeneratesConsistentString(): void
    {
        $k1 = \ReportCache::key('summary', '2026', 'January', 'Brgy1');
        $k2 = \ReportCache::key('summary', '2026', 'January', 'Brgy1');
        $this->assertSame($k1, $k2);
    }

    public function testKeyDiffersWithDifferentParts(): void
    {
        $k1 = \ReportCache::key('summary', '2026', 'January');
        $k2 = \ReportCache::key('summary', '2026', 'July');
        $this->assertNotSame($k1, $k2);
    }

    public function testSetAndGetReturnsStoredValue(): void
    {
        $key  = \ReportCache::key('test', 'get_set');
        $data = ['rows' => [1, 2, 3], 'total' => 3];
        \ReportCache::set($key, $data);
        $result = \ReportCache::get($key);
        $this->assertSame($data, $result);
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $this->assertNull(\ReportCache::get(\ReportCache::key('nonexistent')));
    }

    public function testInvalidateClearsStoredValues(): void
    {
        $key = \ReportCache::key('test', 'invalidate');
        \ReportCache::set($key, ['data' => true]);
        \ReportCache::invalidate();
        $this->assertNull(\ReportCache::get($key));
    }
}
