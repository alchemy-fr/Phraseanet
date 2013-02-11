<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef\OptionType;

use Alchemy\Phrasea\Media\Subdef\OptionType\Enum;
use Alchemy\Phrasea\Media\Subdef\OptionType\OptionType;

class EnumTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Enum
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Enum('Numo', 'enumerateur', array('un', 'dos', 'tres'), 'dos');
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Enum::setValue
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Enum::getValue
     */
    public function testSetValue()
    {
        $this->assertEquals('dos', $this->object->getValue());
        $this->object->setValue('tres');
        $this->assertEquals('tres', $this->object->getValue());
        $this->object->setValue('');
        $this->assertNull($this->object->getValue());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Enum::setValue
     * @expectedException \Exception_InvalidArgument
     */
    public function testSetWrongValue()
    {
        $this->object->setValue('deux');
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Enum::getType
     */
    public function testGetType()
    {
        $this->assertEquals(OptionType::TYPE_ENUM, $this->object->getType());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Enum::getAvailableValues
     */
    public function testGetAvailableValues()
    {
        $this->assertEquals(array('un', 'dos', 'tres'), $this->object->getAvailableValues());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Enum::getName
     */
    public function testGetName()
    {
        $this->assertEquals('enumerateur', $this->object->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Enum::getDisplayName
     */
    public function testGetDisplayName()
    {
        $this->assertEquals('Numo', $this->object->getDisplayName());
    }
}
