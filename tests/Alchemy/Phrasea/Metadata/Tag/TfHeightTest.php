<?php

namespace Alchemy\Phrasea\Metadata\Tag;

class TfHeightTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Alchemy\Phrasea\Metadata\Tag\TfHeight
     */
    public function testObject()
    {
        $object = new TfHeight();

        $this->assertInstanceOf('\\PHPExiftool\\Driver\\Tag', $object);
        $this->assertInternalType('string', $object->getDescription());
        $this->assertInternalType('string', $object->getGroupName());
        $this->assertInternalType('string', $object->getId());
        $this->assertInternalType('string', $object->getName());
        $this->assertEquals(0, strpos('Phraseanet:', $object->getTagname()));
    }
}
