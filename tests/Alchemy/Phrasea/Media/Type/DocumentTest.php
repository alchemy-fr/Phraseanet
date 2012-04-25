<?php

namespace Alchemy\Phrasea\Media\Type;

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
