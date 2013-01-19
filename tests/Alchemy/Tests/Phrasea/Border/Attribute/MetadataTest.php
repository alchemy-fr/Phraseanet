<?php

namespace Alchemy\Tests\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Border\Attribute\Metadata;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use PHPExiftool\Driver\Tag\IPTC\ObjectName;
use PHPExiftool\Driver\Value\Mono;
use PHPExiftool\Driver\Metadata\Metadata as ExiftoolMeta;

class MetadataTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Metadata
     */
    protected $object;
    protected $metadata;

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Attribute
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $tag = new ObjectName();
        $value = new Mono('Stockhausen !');

        $this->metadata = new ExiftoolMeta($tag, $value);

        $this->object = new Metadata($this->metadata);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::__destruct
     */
    public function tearDown()
    {
        $this->object = null;
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::getName
     */
    public function testGetName()
    {
        $this->assertEquals(AttributeInterface::NAME_METADATA, $this->object->getName());
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
        $loaded = Metadata::loadFromString(self::$DI['app'], $this->object->asString());

        $this->assertEquals($this->object, $loaded);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringFail()
    {
        \PHPUnit_Framework_Error_Notice::$enabled = false;

        Metadata::loadFromString(self::$DI['app'], 'Hello String');
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\Metadata::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringWrongObject()
    {
        Metadata::loadFromString(self::$DI['app'], serialize(new \stdClass()));
    }
}
