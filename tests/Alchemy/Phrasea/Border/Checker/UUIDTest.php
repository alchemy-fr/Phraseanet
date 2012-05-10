<?php

namespace Alchemy\Phrasea\Border\Checker;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

use Alchemy\Phrasea\Border\File;

class UUIDTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var UUID
     */
    protected $object;
    protected $filename;
    protected static $need_records = 1;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new UUID;
        $this->filename = __DIR__ . '/../../../../../tmp/test001.CR2';
        copy(__DIR__ . '/../../../../testfiles/test001.CR2', $this->filename);
    }

    public function tearDown()
    {
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\UUID::check
     */
    public function testCheck()
    {
        $response = $this->object->check(self::$core['EM'], new File($this->filename, self::$collection));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertFalse($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\UUID::check
     */
    public function testCheckNoFile()
    {
        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', array('getUUID'), array($this->filename, self::$collection));

        $mock
            ->expects($this->once())
            ->method('getUUID')
            ->will($this->returnValue(\random::generatePassword(3)))
        ;

        $response = $this->object->check(self::$core['EM'], $mock);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\UUID::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', $this->object->getMessage());
    }
}
