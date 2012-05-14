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

        $object = new Dimension(800);
        $response = $object->check(self::$core['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(500);
        $response = $object->check(self::$core['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(400);
        $response = $object->check(self::$core['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());

        $object = new Dimension(600, 500);
        $response = $object->check(self::$core['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());

        $object = new Dimension(600, 400);
        $response = $object->check(self::$core['EM'], $File);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());

        $object = new Dimension(200, 200);
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
                $width = $dimensions[0];

                if (isset($dimensions[1])) {
                    $height = $dimensions[1];
                }

                new Dimension($width, $height);
                $this->fail(sprintf('Exception raised with dimensions %s and %s', $width, $height));
            } catch (\InvalidArgumentException $e) {

            }
        }
    }

    public function getWrongDimensions()
    {
        return array(
            array(0),
            array(-1),
            array(5, -4),
            array(5, 'a'),
            array('a', 5),
            array('a', 'b'),
            array('a'),
            array(0, 35),
            array(30, 0),
        );
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Dimension::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', Dimension::getMessage());
    }
}
