<?php

namespace Alchemy\Phrasea\Border\Checker;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

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

        $File = new \Alchemy\Phrasea\Border\File(self::$DI['app'], $media, self::$DI['collection']);

        $object = new Dimension(self::$DI['app'], array('width' => 800));
        $response = $object->check(self::$DI['app']['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(self::$DI['app'], array('width' => 500));
        $response = $object->check(self::$DI['app']['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(self::$DI['app'], array('width' => 400));
        $response = $object->check(self::$DI['app']['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());

        $object = new Dimension(self::$DI['app'], array('width' => 600, 'height' => 500));
        $response = $object->check(self::$DI['app']['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(self::$DI['app'], array('width' => 600, 'height' => 400));
        $response = $object->check(self::$DI['app']['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());

        $object = new Dimension(self::$DI['app'], array('width' => 200, 'height' => 200));
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

                new Dimension(self::$DI['app'], array('width' => $width,'height' => $height));
                $this->fail(sprintf('Exception raised with dimensions %s and %s', $width, $height));
            } catch (\InvalidArgumentException $e) {

            }
        }
    }

    public function getWrongDimensions()
    {
        return array(
            array('width' => 0),
            array('width' => -1),
            array('width' => 5, 'height' => -4),
            array('width' => 5, 'height' => 'a'),
            array('width' => 'a', 'height' => 5),
            array('width' => 'a', 'height' => 'b'),
            array('width' => 'a'),
            array('width' => 0, 'height' => 35),
            array('width' => 30, 'height' => 0)
        );
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Dimension::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', Dimension::getMessage());
    }

     /**
     * @expectedException InvalidArgumentException
     */
    public function testContructorInvalidArgumentException()
    {
        new Dimension(self::$DI['app'], array('witdh' => 38));
    }
}
