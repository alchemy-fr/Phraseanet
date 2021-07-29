<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Image;
use Alchemy\Phrasea\Media\Subdef\OptionType\OptionType;
use Alchemy\Phrasea\Media\Subdef\Provider;
use Alchemy\Tests\Tools\TranslatorMockTrait;

/**
 * @group functional
 * @group legacy
 */
class ProviderTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /**
     * @var Provider
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new Image($this->createTranslatorMock());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Provider::getOptions
     */
    public function testGetOptions()
    {
        $this->assertTrue(is_array($this->object->getOptions()));

        foreach ($this->object->getOptions() as $option) {
            $this->assertInstanceOf(OptionType::class, $option);
        }
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Provider::getOption
     */
    public function testGetOption()
    {
        $option = $this->object->getOption(Image::OPTION_SIZE);

        $this->assertInstanceOf(OptionType::class, $option);
        $this->assertEquals(Image::OPTION_SIZE, $option->getName());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Provider::setOptionValue
     */
    public function testSetOptionValue()
    {
        $this->object->setOptionValue(Image::OPTION_SIZE, 300);
        $option = $this->object->getOption(Image::OPTION_SIZE);
        $this->assertEquals(300, $option->getValue());
    }
}
