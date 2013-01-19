<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Image;
use Alchemy\Phrasea\Media\Type\Type;

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
