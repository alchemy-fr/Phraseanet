<?php

namespace Alchemy\Tests\Phrasea\Command\Developper\Utils;

use Alchemy\Phrasea\Command\Developer\Utils\RecessDriver;

class RecessDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testGetCreate()
    {
        $recessDriver = RecessDriver::create();

        $this->assertInstanceOf('Alchemy\BinaryDriver\BinaryInterface', $recessDriver);
    }

    public function testGetName()
    {
        $recessDriver = RecessDriver::create();

        $this->assertEquals('recess', $recessDriver->getName());
    }
}
