<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Checker\UUID;

class UUIDTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var UUID
     */
    protected $object;
    protected $filename;
    protected $media;

    public function setUp()
    {
        parent::setUp();
        $this->object = new UUID(self::$DI['app']);
        $this->filename = __DIR__ . '/../../../../../../tmp/test001.CR2';
        copy(__DIR__ . '/../../../../../files/test001.CR2', $this->filename);
        $this->media = self::$DI['app']['mediavorus']->guess($this->filename);
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
     * @covers Alchemy\Phrasea\Border\Checker\UUID::check
     */
    public function testCheck()
    {
        $response = $this->object->check(self::$DI['app']['EM'], new File(self::$DI['app'], $this->media, self::$DI['collection']));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertFalse($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\UUID::check
     */
    public function testCheckNoFile()
    {
        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', array('getUUID'), array(self::$DI['app'], $this->media, self::$DI['collection']));

        $mock
            ->expects($this->once())
            ->method('getUUID')
            ->will($this->returnValue(\random::generatePassword(3)))
        ;

        $response = $this->object->check(self::$DI['app']['EM'], $mock);

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
