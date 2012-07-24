<?php

namespace Alchemy\Phrasea\Border\Checker;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

use Alchemy\Phrasea\Border\File;

class FilenameTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Filename
     */
    protected $object;
    protected $filename;
    protected $media;

    /**
     * @covers Alchemy\Phrasea\Border\Checker\CheckerInterface
     * @covers Alchemy\Phrasea\Border\Checker\Filename::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new Filename;
        $this->filename = __DIR__ . '/../../../../../tmp/test001.CR2';
        copy(__DIR__ . '/../../../../testfiles/test001.CR2', $this->filename);
        $this->media = self::$core['mediavorus']->guess(new \SplFileInfo($this->filename));
    }

    public function tearDown()
    {
        $this->media = null;
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Filename::check
     */
    public function testCheck()
    {
        $response = $this->object->check(self::$core['EM'], new File($this->media, self::$collection));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertFalse($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Filename::check
     */
    public function testCheckNoFile()
    {
        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', array('getOriginalName'), array($this->media, self::$collection));

        $mock
            ->expects($this->once())
            ->method('getOriginalName')
            ->will($this->returnValue(\random::generatePassword(32)))
        ;

        $response = $this->object->check(self::$core['EM'], $mock);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertTrue($response->isOk());

        $mock = null;
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Filename::__construct
     * @covers Alchemy\Phrasea\Border\Checker\Filename::check
     */
    public function testCheckSensitive()
    {
        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', array('getOriginalName'), array($this->media, self::$collection));

        $mock
            ->expects($this->any())
            ->method('getOriginalName')
            ->will($this->returnValue(strtoupper($this->media->getFile()->getFilename())))
        ;

        $response = $this->object->check(self::$core['EM'], $mock);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertFalse($response->isOk());

        $objectSensitive = new Filename(array('sensitive'        => true));
        $responseSensitive = $objectSensitive->check(self::$core['EM'], $mock);

        $this->assertTrue($responseSensitive->isOk());

        $mock = null;
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Filename::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', $this->object->getMessage());
    }
}
