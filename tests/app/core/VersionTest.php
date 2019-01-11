<?php

namespace Alchemy\Tests\Phrasea\Core;

use Alchemy\Phrasea\Core\Version;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function testGetNumber()
    {
        $version = new Version();
        $this->assertTrue(is_string($version->getNumber()));
        $this->assertRegExp('/[\d]{1}\.[\d]{1,2}\.[\d]{1,2}/', $version->getNumber());
    }

    public function testGetName()
    {
        $version = new Version();
        $this->assertTrue(is_string($version->getName()));
        $this->assertTrue(strlen($version->getName()) > 3);
    }
}
