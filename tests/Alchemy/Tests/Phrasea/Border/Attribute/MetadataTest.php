<?php

namespace Alchemy\Tests\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Border\Attribute\Metadata;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Metadata\TagFactory;
use PHPExiftool\Driver\Value\Mono;
use PHPExiftool\Driver\Metadata\Metadata as ExiftoolMeta;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MetadataTest extends \PhraseanetTestCase
{
    /**
     * @var Metadata
     */
    protected $object;
    protected $metadata;

    public function setUp(): void
    {
        parent::setUp();
        $tag = TagFactory::getFromRDFTagname('IPTC:ObjectName');
        $value = new Mono('Stockhausen !');

        $this->metadata = new ExiftoolMeta($tag, $value);

        $this->object = new Metadata($this->metadata);
    }

    public function tearDown(): void
    {
        $this->object = null;
        parent::tearDown();
    }

    public function testGetName()
    {
        $this->assertEquals(AttributeInterface::NAME_METADATA, $this->object->getName());
    }

    public function testGetValue()
    {
        $this->assertEquals($this->metadata, $this->object->getValue());
    }

    public function testAsString()
    {
        $this->assertIsString($this->object->asString());
    }

    public function testLoadFromString()
    {
        $loaded = Metadata::loadFromString(self::$DI['app'], $this->object->asString());

        $this->assertEquals($this->object, $loaded);
    }

    public function testLoadFromStringFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        \PHPUnit\Framework\Error\Notice::$enabled = false;

        Metadata::loadFromString(self::$DI['app'], 'Hello String');
    }

    public function testLoadFromStringWrongObject()
    {
        $this->expectException(\InvalidArgumentException::class);
        Metadata::loadFromString(self::$DI['app'], serialize(new \stdClass()));
    }
}
