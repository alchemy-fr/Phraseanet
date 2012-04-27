<?php

namespace Alchemy\Phrasea\Media\Subdef\OptionType;

class BooleanTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Boolean
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Boolean('Booleen', 'boolean', true);
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Boolean::setValue
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Boolean::getValue
     */
    public function testSetValue()
    {
        $this->assertTrue($this->object->getValue());
        $this->object->setValue(false);
        $this->assertFalse($this->object->getValue());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Boolean::getType
     */
    public function testGetType()
    {
        $this->assertEquals(OptionType::TYPE_BOOLEAN, $this->object->getType());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Boolean::getName
     */
    public function testGetName()
    {
        $this->assertEquals('boolean', $this->object->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Boolean::getDisplayName
     */
    public function testGetDisplayName()
    {
        $this->assertEquals('Booleen', $this->object->getDisplayName());
    }

}
