<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Checker\UUID;

/**
 * @group functional
 * @group legacy
 */
class UUIDTest extends \PhraseanetTestCase
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
        $this->filename = __DIR__ . '/../../../../../../tmp/test001.jpg';
        copy(__DIR__ . '/../../../../../files/test001.jpg', $this->filename);
        $this->media = self::$DI['app']['mediavorus']->guess($this->filename);
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->media = null;
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\UUID::check
     */
    public function testCheck()
    {
        $response = $this->object->check(self::$DI['app']['orm.em'], new File(self::$DI['app'], $this->media, self::$DI['collection']));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertFalse($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\UUID::check
     */
    public function testCheckNoFile()
    {
        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', ['getUUID'], [self::$DI['app'], $this->media, self::$DI['collection']]);

        $mock
            ->expects($this->once())
            ->method('getUUID')
            ->will($this->returnValue(self::$DI['app']['random.low']->generateString(3)))
        ;

        $response = $this->object->check(self::$DI['app']['orm.em'], $mock);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\UUID::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', $this->object->getMessage($this->createTranslatorMock()));
    }
}
