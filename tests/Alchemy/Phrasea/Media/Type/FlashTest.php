<?php

namespace Alchemy\Phrasea\Media\Type;

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
