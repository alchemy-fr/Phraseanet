<?php

namespace Alchemy\Tests\Phrasea\Border\Checker;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Checker\Extension;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ExtensionTest extends \PhraseanetTestCase
{
    /**
     * @var Extension
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Extension(self::$DI['app'], ['extensions' => ['jpg', 'png', 'tiff']]);
    }

    public function testCheck()
    {
        $tests = [
            'jpg'  => true,
            'JPG'  => true,
            'tiff' => true,
            'exe'  => false,
        ];

        foreach ($tests as $extension => $result) {

            $spl = $this->createMock('\\Symfony\\Component\\HttpFoundation\\File\\File');

            $spl
                ->method('getExtension')
                ->will($this->returnValue($extension));

            $media = $this->createMock('\\MediaVorus\\Media\\Image');
            $media
                ->method('getFile')
                ->will($this->returnValue($spl));

            $File = new File(self::$DI['app'], $media, self::$DI['collection']);

            $response = $this->object->check(self::$DI['app']['orm.em'], $File);

            $this->assertEquals($result, $response->isOk());
        }
    }

    public function testGetMessage()
    {
        $this->assertIsString($this->object->getMessage($this->createTranslatorMock()));
    }

    public function testContructorInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Extension(self::$DI['app'], [['jpg', 'png', 'tiff']]);
    }
}
