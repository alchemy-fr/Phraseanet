<?php

namespace Alchemy\Phrasea\Border\Attribute;

class MetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Metadata
     */
    protected $object;
    protected $metadata;

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::__construct
     */
    protected function setUp()
    {
        parent::setUp();
        $tag = new \PHPExiftool\Driver\Tag\MXF\ObjectName();
        $value = new \PHPExiftool\Driver\Value\Mono('Stockhausen !');

        $this->metadata = new \PHPExiftool\Driver\Metadata\Metadata($tag, $value);

        $this->object = new Metadata($this->metadata);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::__destruct
     */
    protected function tearDown()
    {
        $this->object = null;
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::getName
     */
    public function testGetName()
    {
        $this->assertEquals(Attribute::NAME_METADATA, $this->object->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::getValue
     */
    public function testGetValue()
    {
        $this->assertEquals($this->metadata, $this->object->getValue());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::asString
     */
    public function testAsString()
    {
        $this->assertInternalType('string', $this->object->asString());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::loadFromString
     */
    public function testLoadFromString()
    {
        $loaded = Metadata::loadFromString($this->object->asString());

        $this->assertEquals($this->object, $loaded);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringFail()
    {
        \PHPUnit_Framework_Error_Notice::$enabled = false;

        Metadata::loadFromString('Hello String');
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringWrongObject()
    {
        Metadata::loadFromString(serialize(new \stdClass()));
    }
}
