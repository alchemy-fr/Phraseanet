<?php

namespace Alchemy\Phrasea\Border\Checker;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class MediaTypeTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var MediaType
     */
    protected $object;

    /**
     * @covers Alchemy\Phrasea\Border\Checker\Checker
     * @covers Alchemy\Phrasea\Border\Checker\MediaType::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new MediaType(array(MediaType::TYPE_IMAGE));
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\MediaType::check
     */
    public function testCheck()
    {
        $media = \MediaVorus\MediaVorus::guess(new \SplFileInfo(__DIR__ . '/../../../../testfiles/test001.CR2'));
        $file = new \Alchemy\Phrasea\Border\File($media, self::$collection);
        $response = $this->object->check(self::$core['EM'], $file);

        $this->assertTrue($response->isOk());

        $object = new MediaType(array(MediaType::TYPE_VIDEO, MediaType::TYPE_AUDIO));

        $media = \MediaVorus\MediaVorus::guess(new \SplFileInfo(__DIR__ . '/../../../../testfiles/test001.CR2'));
        $file = new \Alchemy\Phrasea\Border\File($media, self::$collection);
        $response = $object->check(self::$core['EM'], $file);

        $this->assertFalse($response->isOk());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Checker\MediaType::getMessage
     */
    public function testGetMessage()
    {
        $this->assertInternalType('string', $this->object->getMessage());
    }
}
