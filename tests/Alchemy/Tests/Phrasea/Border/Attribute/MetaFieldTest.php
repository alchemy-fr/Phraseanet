<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Border\Attribute\MetaField;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;

class MetaFieldTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var MetaField
     */
    protected $object;
    protected $before;
    protected $beforeNotice;
    protected $databox_field;

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\MetaField::__construct
     */
    public function setUp()
    {
        $this->value = array("Un éléphant ça trompe");
        foreach (self::$DI['collection']->get_databox()->get_meta_structure() as $databox_field) {
            $this->databox_field = $databox_field;
            break;
        }
        if (! $this->databox_field) {
            $this->markTestSkipped('No databox field found');
        }
        $this->object = new MetaField($this->databox_field, $this->value);

        $this->before = \PHPUnit_Framework_Error_Warning::$enabled;
        $this->beforeNotice = \PHPUnit_Framework_Error_Notice::$enabled;
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\MetaField::__destruct
     */
    public function tearDown()
    {
        \PHPUnit_Framework_Error_Warning::$enabled = $this->before;
        \PHPUnit_Framework_Error_Notice::$enabled = $this->beforeNotice;
        $this->object = null;
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\MetaField::__construct
     */
    public function testConstruct()
    {
        new MetaField($this->databox_field, array(0.57));
        new MetaField($this->databox_field, array(3));
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\MetaField::getName
     */
    public function testGetName()
    {
        $this->assertEquals(AttributeInterface::NAME_METAFIELD, $this->object->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\MetaField::getField
     */
    public function testGetField()
    {
        $this->assertEquals($this->databox_field, $this->object->getField());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\MetaField::getValue
     */
    public function testGetValue()
    {
        $this->assertEquals($this->value, $this->object->getValue());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\MetaField::asString
     */
    public function testAsString()
    {
        $this->assertInternalType('string', $this->object->asString());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\MetaField::loadFromString
     */
    public function testLoadFromString()
    {
        $this->assertEquals($this->object, MetaField::loadFromString(self::$DI['app'], $this->object->asString()));
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\MetaField::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringFail()
    {
        MetaField::loadFromString(self::$DI['app'], 'Elephant');
    }

    /**
     * @covers Alchemy\Phrasea\Border\Attribute\MetaField::loadFromString
     * @expectedException \InvalidArgumentException
     */
    public function testLoadFromStringFailSerialize()
    {
        \PHPUnit_Framework_Error_Warning::$enabled = false;
        \PHPUnit_Framework_Error_Notice::$enabled = false;
        MetaField::loadFromString(self::$DI['app'], serialize(array('Elephant', 'sbas_id' => self::$DI['collection']->get_sbas_id(), 'id' => 0)));
    }
}
