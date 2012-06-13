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
     * @covers Alchemy\Phrasea\Border\Checker\Checker
     * @covers Alchemy\Phrasea\Border\Checker\Dimension::__construct
     * @covers Alchemy\Phrasea\Border\Checker\Dimension::check
     */
    public function testCheckSameDims()
    {
        $spl = new \SplFileInfo(__DIR__ . '/../../../../testfiles/test001.CR2');

        $media = $this->getMock('\\MediaVorus\\Media\\Image', array('getWidth', 'getHeight'), array($spl));

        $media->expects($this->any())
            ->method('getWidth')
            ->will($this->returnValue('600'));
        $media->expects($this->any())
            ->method('getHeight')
            ->will($this->returnValue('400'));

        $File = new \Alchemy\Phrasea\Border\File($media, self::$collection);

        $object = new Dimension(array('width' => 800));
        $response = $object->check(self::$core['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(array('width' => 500));
        $response = $object->check(self::$core['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(array('width' => 400));
        $response = $object->check(self::$core['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());

        $object = new Dimension(array('width' => 600, 'height' => 500));
        $response = $object->check(self::$core['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(array('width' => 600, 'height' => 400));
        $response = $object->check(self::$core['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());

        $object = new Dimension(array('width' => 200, 'height' => 200));
        $response = $object->check(self::$core['EM'], $File);
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

                new Dimension(array('width' => $width,'height' => $height));
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
        new Dimension(array('witdh' => 38));
    }
}
