<?php

namespace Alchemy\Phrasea\Media\Subdef\OptionType;

class RangeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Range
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Range('Rangers', 'name', 3, 8, 6, 2);
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Range::setValue
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Range::getValue
     */
    public function testSetValue()
    {
        $this->assertEquals(6, $this->object->getValue());
        $this->object->setValue(8);
        $this->assertEquals(8, $this->object->getValue());
        $this->object->setValue('');
        $this->assertNull($this->object->getValue());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Range::setValue
     * @expectedException \Exception_InvalidArgument
     */
    public function testSetWrongValue()
    {
        $this->object->setValue(9);
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Range::getType
     */
    public function testGetType()
    {
        $this->assertEquals(OptionType::TYPE_RANGE, $this->object->getType());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Range::getName
     */
    public function testGetName()
    {
        $this->assertEquals('name', $this->object->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Range::getStep
     */
    public function testGetStep()
    {
        $this->assertEquals(2, $this->object->getStep());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Range::getMinValue
     */
    public function testGetMinValue()
    {
        $this->assertEquals(3, $this->object->getMinValue());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Range::getMaxValue
     */
    public function testGetMaxValue()
    {
        $this->assertEquals(8, $this->object->getMaxValue());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Range::getDisplayName
     */
    public function testGetDisplayName()
    {
        $this->assertEquals('Rangers', $this->object->getDisplayName());
    }
}
