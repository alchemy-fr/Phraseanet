<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Utilities\Less;

use Alchemy\Phrasea\Utilities\Less\RecessDriver;

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
