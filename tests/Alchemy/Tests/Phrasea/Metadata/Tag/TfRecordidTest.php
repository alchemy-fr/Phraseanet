<?php

namespace Alchemy\Tests\Phrasea\Metadata\Tag;

use Alchemy\Phrasea\Metadata\Tag\TfRecordid;

class TfRecordidTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Alchemy\Phrasea\Metadata\Tag\TfRecordid
     */
    public function testObject()
    {
        $object = new TfRecordid();

        $this->assertInstanceOf('\\PHPExiftool\\Driver\\TagInterface', $object);
        $this->assertInternalType('string', $object->getDescription());
        $this->assertInternalType('string', $object->getGroupName());
        $this->assertInternalType('string', $object->getId());
        $this->assertInternalType('string', $object->getName());
        $this->assertEquals(0, strpos('Phraseanet:', $object->getTagname()));
    }
}
