<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Checker\Dimension;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class DimensionTest extends \PhraseanetTestCase
{
    /**
     * @var Dimension
     */
    protected $object;

    public function testCheckSameDims()
    {
        $media = $this->createMock('\\MediaVorus\\Media\\Image');

        $media
            ->method('getWidth')
            ->will($this->returnValue('600'));
        $media
            ->method('getHeight')
            ->will($this->returnValue('400'));
        $media
            ->method('getFile')
            ->will($this->returnValue(new \SplFileInfo(__FILE__)));

        $File = new File(self::$DI['app'], $media, self::$DI['collection']);

        $object = new Dimension(self::$DI['app'], ['width' => 800]);
        $response = $object->check(self::$DI['app']['orm.em'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(self::$DI['app'], ['width' => 500]);
        $response = $object->check(self::$DI['app']['orm.em'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(self::$DI['app'], ['width' => 400]);
        $response = $object->check(self::$DI['app']['orm.em'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());

        $object = new Dimension(self::$DI['app'], ['width' => 600, 'height' => 500]);
        $response = $object->check(self::$DI['app']['orm.em'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(self::$DI['app'], ['width' => 600, 'height' => 400]);
        $response = $object->check(self::$DI['app']['orm.em'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());

        $object = new Dimension(self::$DI['app'], ['width' => 200, 'height' => 200]);
        $response = $object->check(self::$DI['app']['orm.em'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getWrongDimensions')]
    public function testInvalidVars()
    {
        foreach ($this->getWrongDimensions() as $dimensions) {

            try {
                $width = $height = null;
                $width = $dimensions['width'];

                if (isset($dimensions['height'])) {
                    $height = $dimensions['height'];
                }

                new Dimension(self::$DI['app'], ['width' => $width,'height' => $height]);
                $this->fail(sprintf('Exception raised with dimensions %s and %s', $width, $height));
            } catch (\InvalidArgumentException $e) {

            }
        }
    }

    public static function getWrongDimensions()
    {
        return [
            ['width' => 0],
            ['width' => -1],
            ['width' => 5, 'height' => -4],
            ['width' => 5, 'height' => 'a'],
            ['width' => 'a', 'height' => 5],
            ['width' => 'a', 'height' => 'b'],
            ['width' => 'a'],
            ['width' => 0, 'height' => 35],
            ['width' => 30, 'height' => 0]
        ];
    }

    public function testGetMessage()
    {
        $checker = new Dimension(self::$DI['app'], ['width' => 800]);
        $this->assertIsString($checker->getMessage($this->createTranslatorMock()));
    }

     public function testContructorInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Dimension(self::$DI['app'], ['witdh' => 38]);
    }
}
