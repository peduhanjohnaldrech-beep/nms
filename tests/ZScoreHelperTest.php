<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for ZScoreHelper classification methods.
 * These do not require a database connection.
 */
class ZScoreHelperTest extends TestCase
{
    // ── WFA Classification ────────────────────────────────────────────

    public function testClassifyWfaSuw(): void
    {
        $this->assertSame('SUW', \ZScoreHelper::classifyWFA(-3.1));
        $this->assertSame('SUW', \ZScoreHelper::classifyWFA(-4.0));
    }

    public function testClassifyWfaUw(): void
    {
        $this->assertSame('UW', \ZScoreHelper::classifyWFA(-3.0));
        $this->assertSame('UW', \ZScoreHelper::classifyWFA(-2.5));
        $this->assertSame('UW', \ZScoreHelper::classifyWFA(-2.01));
    }

    public function testClassifyWfaNormal(): void
    {
        $this->assertSame('Normal', \ZScoreHelper::classifyWFA(-2.0));
        $this->assertSame('Normal', \ZScoreHelper::classifyWFA(0.0));
        $this->assertSame('Normal', \ZScoreHelper::classifyWFA(2.0));
    }

    public function testClassifyWfaOverweight(): void
    {
        $this->assertSame('OW', \ZScoreHelper::classifyWFA(2.5));
        $this->assertSame('OW', \ZScoreHelper::classifyWFA(3.0));
    }

    public function testClassifyWfaObese(): void
    {
        $this->assertSame('OB', \ZScoreHelper::classifyWFA(3.1));
        $this->assertSame('OB', \ZScoreHelper::classifyWFA(5.0));
    }

    // ── HFA Classification ────────────────────────────────────────────

    public function testClassifyHfaSeverelyStuned(): void
    {
        $this->assertSame('SSt', \ZScoreHelper::classifyHFA(-3.1));
        $this->assertSame('SSt', \ZScoreHelper::classifyHFA(-5.0));
    }

    public function testClassifyHfaStunted(): void
    {
        $this->assertSame('St', \ZScoreHelper::classifyHFA(-3.0));
        $this->assertSame('St', \ZScoreHelper::classifyHFA(-2.5));
        $this->assertSame('St', \ZScoreHelper::classifyHFA(-2.01));
    }

    public function testClassifyHfaNormal(): void
    {
        $this->assertSame('Normal', \ZScoreHelper::classifyHFA(-2.0));
        $this->assertSame('Normal', \ZScoreHelper::classifyHFA(0.0));
        $this->assertSame('Normal', \ZScoreHelper::classifyHFA(3.0));
    }

    // ── Boundary values ───────────────────────────────────────────────

    public function testWfaBoundaryAtNegativeThree(): void
    {
        // Exactly -3.0 should be UW not SUW
        $this->assertSame('UW', \ZScoreHelper::classifyWFA(-3.0));
    }

    public function testWfaBoundaryAtNegativeTwo(): void
    {
        // Exactly -2.0 should be Normal not UW
        $this->assertSame('Normal', \ZScoreHelper::classifyWFA(-2.0));
    }
}
