<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Checker\Filename;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FilenameTest extends \PhraseanetTestCase
{
    /**
     * @var Filename
     */
    protected $object;
    protected $filename;
    protected $media;

    public function setUp(): void
    {
        parent::setUp();
        // initialize record so that it already exist
        $record = self::$DI['record_1'];
        $this->object = new Filename(self::$DI['app']);
        $this->filename = __DIR__ . '/../../../../../../tmp/test001.jpg';
        copy(__DIR__ . '/../../../../../files/test001.jpg', $this->filename);
        $this->media = self::$DI['app']['mediavorus']->guess($this->filename);
    }

    public function tearDown(): void
    {
        $this->media = null;
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
        parent::tearDown();
    }

    public function testCheck()
    {
        $response = $this->object->check(self::$DI['app']['orm.em'], new File(self::$DI['app'], $this->media, self::$DI['collection']));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertFalse($response->isOk());
    }

    public function testCheckNoFile()
    {
        $mock = $this->getMockBuilder('\\Alchemy\\Phrasea\\Border\\File')->setConstructorArgs([self::$DI['app'], $this->media, self::$DI['collection']])->onlyMethods(['getOriginalName'])->getMock();

        $mock
            ->expects($this->once())
            ->method('getOriginalName')
            ->will($this->returnValue(self::$DI['app']['random.low']->generateString(32)))
        ;

        $response = $this->object->check(self::$DI['app']['orm.em'], $mock);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertTrue($response->isOk());

        $mock = null;
    }

    public function testCheckSensitive()
    {
        $mock = $this->getMockBuilder('\\Alchemy\\Phrasea\\Border\\File')->setConstructorArgs([self::$DI['app'], $this->media, self::$DI['collection']])->onlyMethods(['getOriginalName'])->getMock();

        $mock
            ->method('getOriginalName')
            ->will($this->returnValue(strtoupper($this->media->getFile()->getFilename())))
        ;

        $response = $this->object->check(self::$DI['app']['orm.em'], $mock);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Checker\\Response', $response);

        $this->assertFalse($response->isOk());

        $objectSensitive = new Filename(self::$DI['app'], ['sensitive'        => true]);
        $responseSensitive = $objectSensitive->check(self::$DI['app']['orm.em'], $mock);

        $this->assertTrue($responseSensitive->isOk());

        $mock = null;
    }

    public function testGetMessage()
    {
        $this->assertIsString($this->object->getMessage($this->createTranslatorMock()));
    }
}
