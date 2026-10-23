<?php

namespace Alchemy\Tests\Phrasea\Core;

use Alchemy\Phrasea\Core\Version;

class VersionTest extends \PHPUnit\Framework\TestCase
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
