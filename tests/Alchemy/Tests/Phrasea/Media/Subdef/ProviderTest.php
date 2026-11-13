<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Provider;
use Alchemy\Phrasea\Media\Subdef\Image;
use Alchemy\Tests\Tools\TranslatorMockTrait;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ProviderTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /**
     * @var Provider
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Image($this->createTranslatorMock());
    }

    public function testGetOptions()
    {
        $this->assertTrue(is_array($this->object->getOptions()));

        foreach ($this->object->getOptions() as $option) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\OptionType\\OptionType', $option);
        }
    }

    public function testGetOption()
    {
        $option = $this->object->getOption(Image::OPTION_SIZE);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\OptionType\\OptionType', $option);
        $this->assertEquals(Image::OPTION_SIZE, $option->getName());
    }

    public function testSetOptionValue()
    {
        $this->object->setOptionValue(Image::OPTION_SIZE, 300);
        $option = $this->object->getOption(Image::OPTION_SIZE);
        $this->assertEquals(300, $option->getValue());
    }
}
