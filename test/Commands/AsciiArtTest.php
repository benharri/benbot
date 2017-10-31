<?php

namespace BenBot\Tests;

use PHPUnit\Framework\TestCase;

/**
 * @covers BenBot\Commands\AsciiArt
 */
final class AsciiArtTest extends TestCase
{
    public function testTrueisTrue(): void
    {
        $true = true;
        $this->assertTrue($true);
        $this->assertEquals($true, true);
    }
}
