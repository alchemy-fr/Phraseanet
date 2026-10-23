<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Audio;
use Alchemy\Phrasea\Media\Type\Type;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class AudioTest extends \PhraseanetTestCase
{

    public function testGetType()
    {
        $object = new Audio;
        $this->assertEquals(Type::TYPE_AUDIO, $object->getType());
    }
}
