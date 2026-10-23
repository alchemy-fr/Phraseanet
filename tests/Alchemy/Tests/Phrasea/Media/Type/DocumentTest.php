<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Document;
use Alchemy\Phrasea\Media\Type\Type;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class DocumentTest extends \PhraseanetTestCase
{

    public function testGetType()
    {
        $object = new Document();
        $this->assertEquals(Type::TYPE_DOCUMENT, $object->getType());
    }
}
