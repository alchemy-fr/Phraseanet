<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef\OptionType;

use Alchemy\Phrasea\Media\Subdef\OptionType\Multi;
use Alchemy\Phrasea\Media\Subdef\OptionType\OptionType;

class MultiTest extends \PhraseanetTestCase
{
    /**
     * @var Multi
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new Multi('MUMU', 'multiateur', ['un', 'dos', 'tres'], 'dos');
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\OptionType\Multi::setValue
     */
    public function testSetValue()
    {
        $this->assertEquals(['dos'], $this->object->getValue());
        $this->object->setValue('tres');
        $this->assertEquals(['tres'], $this->object->getValue());
        $this->object->setValue('');
        $this->assertEquals([], $this->object->getValue());
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
        $this->assertEquals(['un', 'dos', 'tres'], $this->object->getAvailableValues());
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
