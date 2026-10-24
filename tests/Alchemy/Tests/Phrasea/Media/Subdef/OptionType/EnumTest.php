<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef\OptionType;

use Alchemy\Phrasea\Media\Subdef\OptionType\Enum;
use Alchemy\Phrasea\Media\Subdef\OptionType\OptionType;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class EnumTest extends \PhraseanetTestCase
{
    /**
     * @var Enum
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Enum('Numo', 'enumerateur', ['un', 'dos', 'tres'], 'dos');
    }

    public function testSetValue()
    {
        $this->assertEquals('dos', $this->object->getValue());
        $this->object->setValue('tres');
        $this->assertEquals('tres', $this->object->getValue());
        $this->object->setValue('');
        $this->assertNull($this->object->getValue());
    }

    public function testSetWrongValue()
    {
        $this->expectException(\Exception_InvalidArgument::class);
        $this->object->setValue('deux');
    }

    public function testGetType()
    {
        $this->assertEquals(OptionType::TYPE_ENUM, $this->object->getType());
    }

    public function testGetAvailableValues()
    {
        $this->assertEquals(['un', 'dos', 'tres'], $this->object->getAvailableValues());
    }

    public function testGetName()
    {
        $this->assertEquals('enumerateur', $this->object->getName());
    }

    public function testGetDisplayName()
    {
        $this->assertEquals('Numo', $this->object->getDisplayName());
    }
}
