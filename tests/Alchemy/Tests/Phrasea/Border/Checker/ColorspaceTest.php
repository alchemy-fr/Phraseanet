<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Checker\Colorspace;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ColorspaceTest extends \PhraseanetTestCase
{
    /**
     * @var Colorspace
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Colorspace(self::$DI['app'], ['colorspaces' => ['RGB', 'cmyk'], 'media_types' => ['Image', 'Document']]);
    }

    public function testCheckImage()
    {
        $media = $this->createMock('\\MediaVorus\\Media\\Image');
        $media
          ->method('getType')
          ->will($this->returnValue('Image'));
        $media->expects($this->once())
            ->method('getColorSpace')
            ->will($this->returnValue('RGB'));
        $media
            ->method('getFile')
            ->will($this->returnValue(new \SplFileInfo(__FILE__)));

        $File = new File(self::$DI['app'], $media, self::$DI['collection']);

        $response = $this->object->check(self::$DI['app']['orm.em'], $File);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());
    }

    public function testCheckVideo()
    {
        $media = $this->createMock('\\MediaVorus\\Media\\Video');
        $media
          ->method('getType')
          ->will($this->returnValue('Video'));
        $media
          ->method('getFile')
          ->will($this->returnValue(new \SplFileInfo(__FILE__)));

        $File = new File(self::$DI['app'], $media, self::$DI['collection']);

        $response = $this->object->check(self::$DI['app']['orm.em'], $File);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());
    }

    public function testCheckFailDocument()
    {
        $media = $this->createMock('\\MediaVorus\\Media\\Document');
        $media->expects($this->once())
          ->method('getColorSpace')
          ->will($this->returnValue(''));
        $media
          ->method('getType')
          ->will($this->returnValue('Document'));
        $media
          ->method('getFile')
          ->will($this->returnValue(new \SplFileInfo(__FILE__)));

        $File = new File(self::$DI['app'], $media, self::$DI['collection']);

        $response = $this->object->check(self::$DI['app']['orm.em'], $File);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertEquals($this->createTranslatorMock()->trans('The file does not match available color'), $response->getMessage($this->createTranslatorMock()));
    }

    public function testGetMessage()
    {
        $this->assertIsString($this->object->getMessage($this->createTranslatorMock()));
    }

    public function testContructorInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Colorspace(self::$DI['app'], [['RGB', 'cmyk']]);
    }
}
