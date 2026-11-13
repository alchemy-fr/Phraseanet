<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef\OptionType;

use Alchemy\Phrasea\Media\Subdef\OptionType\Multi;
use Alchemy\Phrasea\Media\Subdef\OptionType\OptionType;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MultiTest extends \PhraseanetTestCase
{
    /**
     * @var Multi
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Multi('MUMU', 'multiateur', ['un', 'dos', 'tres'], 'dos');
    }

    public function testSetValue()
    {
        $this->assertEquals(['dos'], $this->object->getValue());
        $this->object->setValue('tres');
        $this->assertEquals(['tres'], $this->object->getValue());
        $this->object->setValue('');
        $this->assertEquals([], $this->object->getValue());
    }

    public function testSetWrongValue()
    {
        $this->expectException(\Exception_InvalidArgument::class);
        $this->object->setValue('deux');
    }

    public function testGetType()
    {
        $this->assertEquals(OptionType::TYPE_MULTI, $this->object->getType());
    }

    public function testGetAvailableValues()
    {
        $this->assertEquals(['un', 'dos', 'tres'], $this->object->getAvailableValues());
    }

    public function testGetName()
    {
        $this->assertEquals('multiateur', $this->object->getName());
    }

    public function testGetDisplayName()
    {
        $this->assertEquals('MUMU', $this->object->getDisplayName());
    }
}
