<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef\OptionType;

use Alchemy\Phrasea\Media\Subdef\OptionType\Multi;
use Alchemy\Phrasea\Media\Subdef\OptionType\OptionType;

class MultiTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Multi
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Multi('MUMU', 'multiateur', array('un', 'dos', 'tres'), 'dos');
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Multi::setValue
     */
    public function testSetValue()
    {
        $this->assertEquals(array('dos'), $this->object->getValue());
        $this->object->setValue('tres');
        $this->assertEquals(array('tres'), $this->object->getValue());
        $this->object->setValue('');
        $this->assertEquals(array(), $this->object->getValue());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Multi::setValue
     * @expectedException \Exception_InvalidArgument
     */
    public function testSetWrongValue()
    {
        $this->object->setValue('deux');
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Multi::getType
     */
    public function testGetType()
    {
        $this->assertEquals(OptionType::TYPE_MULTI, $this->object->getType());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Multi::getAvailableValues
     */
    public function testGetAvailableValues()
    {
        $this->assertEquals(array('un', 'dos', 'tres'), $this->object->getAvailableValues());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Multi::getName
     */
    public function testGetName()
    {
        $this->assertEquals('multiateur', $this->object->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Multi::getDisplayName
     */
    public function testGetDisplayName()
    {
        $this->assertEquals('MUMU', $this->object->getDisplayName());
    }
}
