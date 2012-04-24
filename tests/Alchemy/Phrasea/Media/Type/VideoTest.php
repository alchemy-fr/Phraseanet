<?php

namespace Alchemy\Phrasea\Media\Type;

class VideoTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Alchemy\Phrasea\Media\Type\Video::getType
     */
    public function testGetType()
    {
        $object = new Video();
        $this->assertEquals(Type::TYPE_VIDEO, $object->getType());
    }

}
