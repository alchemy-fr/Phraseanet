<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef\OptionType;

use Alchemy\Phrasea\Media\Subdef\OptionType\Boolean;
use Alchemy\Phrasea\Media\Subdef\OptionType\OptionType;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class BooleanTest extends \PhraseanetTestCase
{
    /**
     * @var Boolean
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Boolean('Booleen', 'boolean', true);
    }

    public function testSetValue()
    {
        $this->assertTrue($this->object->getValue());
        $this->object->setValue(false);
        $this->assertFalse($this->object->getValue());
    }

    public function testGetType()
    {
        $this->assertEquals(OptionType::TYPE_BOOLEAN, $this->object->getType());
    }

    public function testGetName()
    {
        $this->assertEquals('boolean', $this->object->getName());
    }

    public function testGetDisplayName()
    {
        $this->assertEquals('Booleen', $this->object->getDisplayName());
    }
}
