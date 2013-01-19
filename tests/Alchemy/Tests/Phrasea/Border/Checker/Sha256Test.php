<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Checker\Sha256;

class Sha256Test extends \PhraseanetPHPUnitAbstract
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
     * @covers Alchemy\Phrasea\Border\Checker\Sha256::check
     */
    public function testCheck()
    {
        $session = new \Entities\LazaretSession();
        self::$DI['app']['EM']->persist($session);

        self::$DI['app']['border-manager']->process($session, File::buildFromPathfile($this->media->getFile()->getPathname(), self::$DI['collection'], self::$DI['app']), null, Manager::FORCE_RECORD);

        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', array('getSha256'), array(self::$DI['app'], $this->media, self::$DI['collection']));

        $mock
            ->expects($this->once())
            ->method('getSha256')
            ->will($this->returnValue($this->media->getHash('sha256', __DIR__ . '/../../../../../files/test001.CR2')))
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
        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', array('getSha256'), array(self::$DI['app'], $this->media, self::$DI['collection']));

        $mock
            ->expects($this->once())
            ->method('getSha256')
            ->will($this->returnValue(\random::generatePassword(3)))
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
        $this->assertInternalType('string', $this->object->getMessage());
    }
}
