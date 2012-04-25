<?php

namespace Alchemy\Phrasea\Media\Type;

class AudioTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Alchemy\Phrasea\Media\Type\Audio::getType
     */
    public function testGetType()
    {
        $object = new Audio;
        $this->assertEquals(Type::TYPE_AUDIO, $object->getType());
    }

}
