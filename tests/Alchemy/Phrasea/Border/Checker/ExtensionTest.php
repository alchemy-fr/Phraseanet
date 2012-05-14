<?php

namespace Alchemy\Phrasea\Border\Checker;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class ExtensionTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Extension
     */
    protected $object;

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Checker
     * @covers Alchemy\Phrasea\Border\Checker\Extension::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new Extension(array('jpg', 'png', 'tiff'));
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

            $spl = $this->getMock('\\Symfony\\Component\\HttpFoundation\\File\\File', array('getExtension'), array(__DIR__ . '/../../../../testfiles/test001.CR2'));

            $spl->expects($this->any())
                ->method('getExtension')
                ->will($this->returnValue($extension));

            $media = $this->getMock('\\MediaVorus\Media\Image', array('getFile'), array($spl));

            $media->expects($this->any())
                ->method('getFile')
                ->will($this->returnValue($spl));

            $File = new \Alchemy\Phrasea\Border\File($media, self::$collection);

            $response = $this->object->check(self::$core['EM'], $File);

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
}
