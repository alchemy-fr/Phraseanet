<?php

namespace Alchemy\Phrasea\Border\Checker;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

use Alchemy\Phrasea\Border\File;

class FilenameTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filename
     */
    protected $object;
    protected $filename;
    protected $media;

    public function setUp()
    {
        parent::setUp();
        $this->object = new Filename;
        $this->filename = __DIR__ . '/../../../../../tmp/test001.CR2';
        copy(__DIR__ . '/../../../../testfiles/test001.CR2', $this->filename);
        $this->media = \MediaVorus\MediaVorus::guess(new \SplFileInfo($this->filename));
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
     * @covers Alchemy\Phrasea\Border\Checker\Filename::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', $this->object->getMessage());
    }
}
