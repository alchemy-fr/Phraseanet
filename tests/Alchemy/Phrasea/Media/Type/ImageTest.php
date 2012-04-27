<?php

namespace Alchemy\Phrasea\Media\Type;

class ImageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Alchemy\Phrasea\Media\Type\Image::getType
     */
    public function testGetType()
    {
        $object = new Image();
        $this->assertEquals(Type::TYPE_IMAGE, $object->getType());
    }
}
