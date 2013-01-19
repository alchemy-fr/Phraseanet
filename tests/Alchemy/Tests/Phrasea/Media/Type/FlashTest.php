<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Flash;
use Alchemy\Phrasea\Media\Type\Type;

class FlashTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Alchemy\Phrasea\Media\Type\Flash::getType
     */
    public function testGetType()
    {
        $object = new Flash();
        $this->assertEquals(Type::TYPE_FLASH, $object->getType());
    }
}
