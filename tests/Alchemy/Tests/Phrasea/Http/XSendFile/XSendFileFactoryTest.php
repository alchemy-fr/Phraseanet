<?php

namespace Alchemy\Tests\Phrasea\Http\XSendFile;

use Alchemy\Phrasea\Http\XSendFile\XSendFileFactory;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class XSendFileFactoryTest extends \PhraseanetTestCase
{
    public function testFactoryCreation()
    {
        $factory = XSendFileFactory::create(self::$DI['app']);
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\XSendFileFactory', $factory);
    }

    public function testFactoryWithXsendFileEnable()
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $factory = new XSendFileFactory($logger, true, 'nginx', $this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\ModeInterface', $factory->getMode());
    }

    public function testFactoryWithXsendFileDisabled()
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $factory = new XSendFileFactory($logger, false, 'nginx',$this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\NullMode', $factory->getMode());
        $this->assertFalse($factory->isXSendFileModeEnabled());
    }

    public function testFactoryWithWrongTypeThrowsAnExceptionIfRequired()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $factory = new XSendFileFactory($logger, true, 'wrong-type', $this->getNginxMapping());
        $factory->getMode(true);
    }

    public function testFactoryWithWrongTypeDoesNotThrowsAnExceptio()
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $logger->expects($this->once())
                ->method('error')
                ->with($this->isString());

        $factory = new XSendFileFactory($logger, true, 'wrong-type', $this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\NullMode', $factory->getMode(false));
    }

     #[\PHPUnit\Framework\Attributes\DataProvider('provideTypes')]
    public function testFactoryType($type, $mapping, $classmode)
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $factory = new XSendFileFactory($logger, true, $type, $mapping);
        $this->assertInstanceOf($classmode, $factory->getMode());
    }

    public static function provideTypes()
    {
        return [
            ['apache', self::getApacheMapping(), 'Alchemy\Phrasea\Http\XSendFile\ApacheMode'],
            ['apache2', self::getApacheMapping(), 'Alchemy\Phrasea\Http\XSendFile\ApacheMode'],
            ['xsendfile', self::getApacheMapping(), 'Alchemy\Phrasea\Http\XSendFile\ApacheMode'],
            ['nginx',self::getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
            ['sendfile',self::getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
            ['xaccel',self::getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
            ['xaccelredirect',self::getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
            ['x-accel',self::getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
            ['x-accel-redirect',self::getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
        ];
    }

    public function testInvalidMappingThrowsAnExceptionIfRequired()
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $logger->expects($this->once())
                ->method('error')
                ->with($this->isString());

        $factory = new XSendFileFactory($logger, true, 'nginx', []);
        $this->expectException('Alchemy\Phrasea\Exception\RuntimeException');
        $factory->getMode(true);
    }

    public function testInvalidMappingDoesNotThrowsAnException()
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $logger->expects($this->once())
                ->method('error')
                ->with($this->isString());

        $factory = new XSendFileFactory($logger, true, 'nginx', []);
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\NginxMode', $factory->getMode(false));
    }

    public function testInvalidMappingDoesNotThrowsAnExceptionByDefault()
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $logger->expects($this->once())
                ->method('error')
                ->with($this->isString());

        $factory = new XSendFileFactory($logger, true, 'nginx', []);
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\NginxMode', $factory->getMode());
    }

    private static function getNginxMapping()
    {
        return [[
            'directory' =>  __DIR__ . '/../../../../files/',
            'mount-point' => '/protected/'
        ]];
    }

    private static function getApacheMapping()
    {
        return [[
            'directory' =>  __DIR__ . '/../../../../files/',
        ]];
    }
}
