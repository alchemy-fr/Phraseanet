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
        $logger = $this->createStub('Psr\Log\LoggerInterface');

        $factory = new XSendFileFactory($logger, true, 'nginx', $this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\ModeInterface', $factory->getMode());
    }

    public function testFactoryWithXsendFileDisabled()
    {
        $logger = $this->createStub('Psr\Log\LoggerInterface');

        $factory = new XSendFileFactory($logger, false, 'nginx',$this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\NullMode', $factory->getMode());
        $this->assertFalse($factory->isXSendFileModeEnabled());
    }

    public function testFactoryWithWrongTypeThrowsAnExceptionIfRequired()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        $logger = $this->createStub('Psr\Log\LoggerInterface');

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
        $logger = $this->createStub('Psr\Log\LoggerInterface');

        $factory = new XSendFileFactory($logger, true, $type, $mapping);
        $this->assertInstanceOf($classmode, $factory->getMode());
    }

    public function provideTypes()
    {
        return [
            ['apache', $this->getApacheMapping(), 'Alchemy\Phrasea\Http\XSendFile\ApacheMode'],
            ['apache2', $this->getApacheMapping(), 'Alchemy\Phrasea\Http\XSendFile\ApacheMode'],
            ['xsendfile', $this->getApacheMapping(), 'Alchemy\Phrasea\Http\XSendFile\ApacheMode'],
            ['nginx',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
            ['sendfile',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
            ['xaccel',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
            ['xaccelredirect',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
            ['x-accel',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
            ['x-accel-redirect',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'],
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

    private function getNginxMapping()
    {
        return [[
            'directory' =>  __DIR__ . '/../../../../files/',
            'mount-point' => '/protected/'
        ]];
    }

    private function getApacheMapping()
    {
        return [[
            'directory' =>  __DIR__ . '/../../../../files/',
        ]];
    }
}
