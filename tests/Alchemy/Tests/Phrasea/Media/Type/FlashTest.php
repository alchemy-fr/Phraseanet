<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Flash;
use Alchemy\Phrasea\Media\Type\Type;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FlashTest extends \PhraseanetTestCase
{

    public function testGetType()
    {
        $object = new Flash();
        $this->assertEquals(Type::TYPE_FLASH, $object->getType());
    }
}
