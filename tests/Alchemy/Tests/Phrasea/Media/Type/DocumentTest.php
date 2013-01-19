<?php

namespace Alchemy\Tests\Phrasea\Media\Type;

use Alchemy\Phrasea\Media\Type\Document;
use Alchemy\Phrasea\Media\Type\Type;

class DocumentTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Alchemy\Phrasea\Media\Type\Document::getType
     */
    public function testGetType()
    {
        $object = new Document();
        $this->assertEquals(Type::TYPE_DOCUMENT, $object->getType());
    }
}
