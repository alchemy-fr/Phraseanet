<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Checker\Sha256;

class Sha256Test extends \PhraseanetTestCase
{
    /**
     * @var Sha256
     */
    protected $object;
    protected $filename;
    protected $media;

    public function setUp()
    {
        parent::setUp();
        $this->object = new Sha256(self::$DI['app']);
        $this->filename = __DIR__ . '/../../../../../../tmp/test001.jpg';
        copy(__DIR__ . '/../../../../../files/test001.jpg', $this->filename);
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
     * @covers Alchemy\Phrasea\Border\Checker\Sha256::check
     */
    public function testCheck()
    {
        $session = self::$DI['app']['EM']->find('Phraseanet:LazaretSession', 1);

        self::$DI['app']['border-manager']->process($session, File::buildFromPathfile($this->media->getFile()->getPathname(), self::$DI['collection'], self::$DI['app']), null, Manager::FORCE_RECORD);

        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', ['getSha256'], [self::$DI['app'], $this->media, self::$DI['collection']]);

        $mock
            ->expects($this->once())
            ->method('getSha256')
            ->will($this->returnValue('7fad283de349b903c850548cda65cf2d86d24c4e3856cdc2b97e47430494b8c8'))
        ;

        $response = $this->object->check(self::$DI['app']['EM'], $mock);
        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);
        $this->assertFalse($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Sha256::check
     */
    public function testCheckNoFile()
    {
        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', ['getSha256'], [self::$DI['app'], $this->media, self::$DI['collection']]);

        $mock
            ->expects($this->once())
            ->method('getSha256')
            ->will($this->returnValue(self::$DI['app']['random.low']->generateString(3)))
        ;

        $response = $this->object->check(self::$DI['app']['EM'], $mock);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Sha256::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', $this->object->getMessage($this->createTranslatorMock()));
    }
}
