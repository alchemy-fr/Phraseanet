<?php

namespace Alchemy\Tests\Phrasea\Core;

use Alchemy\Phrasea\Core\Version;

class VersionTest extends \PhraseanetPHPUnitAbstract
{

    public function testGetNumber()
    {
        $this->assertTrue(is_string(Version::getNumber()));
        $this->assertRegExp('/[\d]{1}\.[\d]{1,2}\.[\d]{1,2}/', Version::getNumber());
    }

    public function testGetName()
    {
        $this->assertTrue(is_string(Version::getName()));
        $this->assertTrue(strlen(Version::getName()) > 3);
    }
}
