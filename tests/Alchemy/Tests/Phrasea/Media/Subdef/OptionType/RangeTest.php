<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef\OptionType;

use Alchemy\Phrasea\Media\Subdef\OptionType\Range;
use Alchemy\Phrasea\Media\Subdef\OptionType\OptionType;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class RangeTest extends \PhraseanetTestCase
{
    /**
     * @var Range
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Range('Rangers', 'name', 3, 8, 6, 2);
    }

    public function testSetValue()
    {
        $this->assertEquals(6, $this->object->getValue());
        $this->object->setValue(8);
        $this->assertEquals(8, $this->object->getValue());
        $this->object->setValue('');
        $this->assertNull($this->object->getValue());
    }

    public function testSetWrongValue()
    {
        $this->expectException(\Exception_InvalidArgument::class);
        $this->object->setValue(9);
    }

    public function testGetType()
    {
        $this->assertEquals(OptionType::TYPE_RANGE, $this->object->getType());
    }

    public function testGetName()
    {
        $this->assertEquals('name', $this->object->getName());
    }

    public function testGetStep()
    {
        $this->assertEquals(2, $this->object->getStep());
    }

    public function testGetMinValue()
    {
        $this->assertEquals(3, $this->object->getMinValue());
    }

    public function testGetMaxValue()
    {
        $this->assertEquals(8, $this->object->getMaxValue());
    }

    public function testGetDisplayName()
    {
        $this->assertEquals('Rangers', $this->object->getDisplayName());
    }
}
