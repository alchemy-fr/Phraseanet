<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Checker\MediaType;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class MediaTypeTest extends \PhraseanetTestCase
{
    /**
     * @var MediaType
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new MediaType(self::$DI['app'], ['mediatypes' => [MediaType::TYPE_IMAGE]]);
    }

    public function testCheck()
    {
        $media = self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/test001.jpg');
        $file = new File(self::$DI['app'], $media, self::$DI['collection']);
        $response = $this->object->check(self::$DI['app']['orm.em'], $file);

        $this->assertTrue($response->isOk());

        $object = new MediaType(self::$DI['app'], ['mediatypes' => [MediaType::TYPE_VIDEO, MediaType::TYPE_AUDIO]]);

        $media = self::$DI['app']['mediavorus']->guess(__DIR__ . '/../../../../../files/test001.jpg');
        $file = new File(self::$DI['app'], $media, self::$DI['collection']);
        $response = $object->check(self::$DI['app']['orm.em'], $file);

        $this->assertFalse($response->isOk());
    }

    public function testGetMessage()
    {
        $this->assertIsString($this->object->getMessage($this->createTranslatorMock()));
    }

    public function testContructorInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        new MediaType(self::$DI['app'], [[MediaType::TYPE_IMAGE]]);
    }
}
