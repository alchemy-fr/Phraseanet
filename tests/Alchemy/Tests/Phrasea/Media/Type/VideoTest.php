<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Video;
use Alchemy\Phrasea\Media\Type\Type;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class VideoTest extends \PhraseanetTestCase
{

    public function testGetType()
    {
        $object = new Video();
        $this->assertEquals(Type::TYPE_VIDEO, $object->getType());
    }
}
