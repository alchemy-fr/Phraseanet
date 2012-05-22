<?php

namespace Alchemy\Phrasea\Metadata\Tag;

class NosourceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Alchemy\Phrasea\Metadata\Tag\Nosource
     */
    public function testObject()
    {
        $object = new Nosource;

        $this->assertInstanceOf('\\PHPExiftool\\Driver\\Tag', $object);
        $this->assertInternalType('string', $object->getDescription());
        $this->assertInternalType('string', $object->getGroupName());
        $this->assertInternalType('string', $object->getId());
        $this->assertInternalType('string', $object->getName());
        $this->assertEquals('', $object->getTagname());
    }
}
