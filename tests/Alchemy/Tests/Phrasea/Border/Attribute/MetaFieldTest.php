<?php

namespace Alchemy\Tests\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Border\Attribute\MetaField;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MetaFieldTest extends \PhraseanetTestCase
{
    /**
     * @var MetaField
     */
    protected $object;
    protected $before;
    protected $beforeNotice;
    protected $databox_field;

    public function setUp(): void
    {
        parent::setUp();
        $this->value = ["Un éléphant ça trompe"];
        foreach (self::$DI['collection']->get_databox()->get_meta_structure() as $databox_field) {
            $this->databox_field = $databox_field;
            break;
        }
        if (! $this->databox_field) {
            $this->markTestSkipped('No databox field found');
        }
        $this->object = new MetaField($this->databox_field, $this->value);

    }

    public function tearDown(): void
    {
        $this->object = null;
        parent::tearDown();
    }

    public function testConstruct()
    {
        new MetaField($this->databox_field, [0.57]);
        new MetaField($this->databox_field, [3]);
    }

    public function testGetName()
    {
        $this->assertEquals(AttributeInterface::NAME_METAFIELD, $this->object->getName());
    }

    public function testGetField()
    {
        $this->assertEquals($this->databox_field, $this->object->getField());
    }

    public function testGetValue()
    {
        $this->assertEquals($this->value, $this->object->getValue());
    }

    public function testAsString()
    {
        $this->assertIsString($this->object->asString());
    }

    public function testLoadFromString()
    {
        $this->assertEquals($this->object, MetaField::loadFromString(self::$DI['app'], $this->object->asString()));
    }

    public function testLoadFromStringFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        MetaField::loadFromString(self::$DI['app'], 'Elephant');
    }

    public function testLoadFromStringFailSerialize()
    {
        $this->expectException(\InvalidArgumentException::class);
        MetaField::loadFromString(self::$DI['app'], serialize(['Elephant', 'sbas_id' => self::$DI['collection']->get_sbas_id(), 'id' => 0]));
    }
}
