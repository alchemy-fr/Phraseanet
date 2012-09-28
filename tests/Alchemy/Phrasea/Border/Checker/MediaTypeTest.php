<?php

namespace Alchemy\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class MediaTypeTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var MediaType
     */
    protected $object;

    /**
     * @covers Alchemy\Phrasea\Border\Checker\CheckerInterface
     * @covers Alchemy\Phrasea\Border\Checker\MediaType::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new MediaType(self::$DI['app'], array('mediatypes' => array(MediaType::TYPE_IMAGE)));
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\MediaType::check
     */
    public function testCheck()
    {
        $media = self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../testfiles/test001.CR2');
        $file = new File(self::$DI['app'], $media, self::$DI['collection']);
        $response = $this->object->check(self::$DI['app']['EM'], $file);

        $this->assertTrue($response->isOk());

        $object = new MediaType(self::$DI['app'], array('mediatypes' => array(MediaType::TYPE_VIDEO, MediaType::TYPE_AUDIO)));

        $media = self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../testfiles/test001.CR2');
        $file = new File(self::$DI['app'], $media, self::$DI['collection']);
        $response = $object->check(self::$DI['app']['EM'], $file);

        $this->assertFalse($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\MediaType::getMessage
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
        new MediaType(self::$DI['app'], array(array(MediaType::TYPE_IMAGE)));
    }
}
