<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Provider;
use Alchemy\Phrasea\Media\Subdef\Image;

class ProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Provider
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Image;
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Provider::getOptions
     */
    public function testGetOptions()
    {
        $this->assertTrue(is_array($this->object->getOptions()));

        foreach ($this->object->getOptions() as $option) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\OptionType\\OptionType', $option);
        }
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Provider::getOption
     */
    public function testGetOption()
    {
        $option = $this->object->getOption(Image::OPTION_SIZE);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Media\\Subdef\\OptionType\\OptionType', $option);
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
