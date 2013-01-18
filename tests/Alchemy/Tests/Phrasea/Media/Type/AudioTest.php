<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Audio;
use Alchemy\Phrasea\Media\Type\Type;

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
