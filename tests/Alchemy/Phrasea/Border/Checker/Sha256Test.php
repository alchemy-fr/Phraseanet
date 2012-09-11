<?php

namespace Alchemy\Phrasea\Border\Checker;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

use Alchemy\Phrasea\Border\File;

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
        $this->object = new Sha256(self::$application);
        $this->filename = __DIR__ . '/../../../../../tmp/test001.CR2';
        copy(__DIR__ . '/../../../../testfiles/test001.CR2', $this->filename);
        $this->media = self::$application['mediavorus']->guess($this->filename);
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
        self::$application['EM']->persist($session);

        self::$application['border-manager']->process($session, File::buildFromPathfile($this->media->getFile()->getPathname(), self::$collection, self::$application['mediavorus']), null, \Alchemy\Phrasea\Border\Manager::FORCE_RECORD);

        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', array('getSha256'), array($this->media, self::$collection));

        $mock
            ->expects($this->once())
            ->method('getSha256')
            ->will($this->returnValue($this->media->getHash('sha256', __DIR__ . '/../../../../testfiles/test001.CR2')))
        ;

        $response = $this->object->check(self::$application['EM'], $mock);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertFalse($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Sha256::check
     */
    public function testCheckNoFile()
    {
        $mock = $this->getMock('\\Alchemy\\Phrasea\\Border\\File', array('getSha256'), array($this->media, self::$collection));

        $mock
            ->expects($this->once())
            ->method('getSha256')
            ->will($this->returnValue(\random::generatePassword(3)))
        ;

        $response = $this->object->check(self::$application['EM'], $mock);

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
