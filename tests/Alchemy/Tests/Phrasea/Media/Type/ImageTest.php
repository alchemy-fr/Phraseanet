<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Image;
use Alchemy\Phrasea\Media\Type\Type;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ImageTest extends \PhraseanetTestCase
{

    public function testGetType()
    {
        $object = new Image();
        $this->assertEquals(Type::TYPE_IMAGE, $object->getType());
    }
}
