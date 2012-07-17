<?php

namespace Alchemy\Phrasea\Border\Checker;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class ColorspaceTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Colorspace
     */
    protected $object;

    /**
     * @covers Alchemy\Phrasea\Border\Checker\CheckerInterface
     * @covers Alchemy\Phrasea\Border\Checker\Colorspace::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new Colorspace(array('colorspaces' => array('RGB', 'cmyk')));
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Colorspace::check
     */
    public function testCheck()
    {
        $spl = new \SplFileInfo(__DIR__ . '/../../../../testfiles/test001.CR2');

        $media = $this->getMock('\\MediaVorus\\Media\\Image', array('getColorSpace'), array($spl));

        $media->expects($this->once())
            ->method('getColorSpace')
            ->will($this->returnValue('RGB'));

        $File = new \Alchemy\Phrasea\Border\File($media, self::$collection);

        $response = $this->object->check(self::$core['EM'], $File);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertTrue($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Colorspace::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', $this->object->getMessage());
    }

     /**
     * @expectedException InvalidArgumentException
     */
    public function testContructorInvalidArgumentException()
    {
        new Colorspace(array(array('RGB', 'cmyk')));
    }
}
