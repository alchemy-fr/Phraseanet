<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Audio;
use Alchemy\Phrasea\Media\Type\Type;

/**
 * @group functional
 * @group legacy
 */
class AudioTest extends \PhraseanetTestCase
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
