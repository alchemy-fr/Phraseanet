<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Checker\Dimension;

class DimensionTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Dimension
     */
    protected $object;

    /**
     * @covers Alchemy\Phrasea\Border\Checker\CheckerInterface
     * @covers Alchemy\Phrasea\Border\Checker\Dimension::__construct
     * @covers Alchemy\Phrasea\Border\Checker\Dimension::check
     */
    public function testCheckSameDims()
    {
        $media = $this
            ->getMockBuilder('\\MediaVorus\\Media\\Image')
            ->disableOriginalConstructor()
            ->getMock();

        $media->expects($this->any())
            ->method('getWidth')
            ->will($this->returnValue('600'));
        $media->expects($this->any())
            ->method('getHeight')
            ->will($this->returnValue('400'));
        $media->expects($this->any())
            ->method('getFile')
            ->will($this->returnValue(new \SplFileInfo(__FILE__)));

        $File = new File(self::$DI['app'], $media, self::$DI['collection']);

        $object = new Dimension(self::$DI['app'], ['width' => 800]);
        $response = $object->check(self::$DI['app']['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(self::$DI['app'], ['width' => 500]);
        $response = $object->check(self::$DI['app']['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(self::$DI['app'], ['width' => 400]);
        $response = $object->check(self::$DI['app']['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());

        $object = new Dimension(self::$DI['app'], ['width' => 600, 'height' => 500]);
        $response = $object->check(self::$DI['app']['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(self::$DI['app'], ['width' => 600, 'height' => 400]);
        $response = $object->check(self::$DI['app']['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());

        $object = new Dimension(self::$DI['app'], ['width' => 200, 'height' => 200]);
        $response = $object->check(self::$DI['app']['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());
    }

    /**
     * @dataProvider getWrongDimensions
     * @covers Alchemy\Phrasea\Border\Checker\Dimension::__construct
     */
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

    public function getWrongDimensions()
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

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Dimension::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', Dimension::getMessage($this->createTranslatorMock()));
    }

     /**
     * @expectedException InvalidArgumentException
     */
    public function testContructorInvalidArgumentException()
    {
        new Dimension(self::$DI['app'], ['witdh' => 38]);
    }
}
