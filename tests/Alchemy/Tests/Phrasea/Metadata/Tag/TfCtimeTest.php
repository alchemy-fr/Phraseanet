<?php

namespace Alchemy\Tests\Phrasea\Metadata\Tag;

use Alchemy\Phrasea\Metadata\Tag\TfCtime;

class TfCtimeTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @covers Alchemy\Phrasea\Metadata\Tag\TfCtime
     */
    public function testObject()
    {
        $object = new TfCtime();

        $this->assertInstanceOf('\\PHPExiftool\\Driver\\TagInterface', $object);
        $this->assertInternalType('string', $object->getDescription());
        $this->assertInternalType('string', $object->getGroupName());
        $this->assertInternalType('string', $object->getId());
        $this->assertInternalType('string', $object->getName());
        $this->assertEquals(0, strpos('Phraseanet:', $object->getTagname()));
    }
}
