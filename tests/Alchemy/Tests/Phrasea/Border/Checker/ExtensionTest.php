<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Checker\Extension;

class ExtensionTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Extension
     */
    protected $object;

    /**
     * @covers Alchemy\Phrasea\Border\Checker\CheckerInterface
     * @covers Alchemy\Phrasea\Border\Checker\Extension::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new Extension(self::$DI['app'], array('extensions' => array('jpg', 'png', 'tiff')));
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Extension::check
     */
    public function testCheck()
    {
        $tests = array(
            'jpg'  => true,
            'JPG'  => true,
            'tiff' => true,
            'exe'  => false,
        );

        foreach ($tests as $extension => $result) {

            $spl = $this->getMock('\\Symfony\\Component\\HttpFoundation\\File\\File', array('getExtension'), array(__DIR__ . '/../../../../../files/test001.CR2'));

            $spl->expects($this->any())
                ->method('getExtension')
                ->will($this->returnValue($extension));

            $media = $this
                ->getMockBuilder('\\MediaVorus\\Media\\Image')
                ->disableOriginalConstructor()
                ->getMock();
            $media->expects($this->any())
                ->method('getFile')
                ->will($this->returnValue($spl));

            $File = new File(self::$DI['app'], $media, self::$DI['collection']);

            $response = $this->object->check(self::$DI['app']['EM'], $File);

            $this->assertEquals($result, $response->isOk());
        }
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Extension::getMessage
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
        new Extension(self::$DI['app'], array(array('jpg', 'png', 'tiff')));
    }
}
