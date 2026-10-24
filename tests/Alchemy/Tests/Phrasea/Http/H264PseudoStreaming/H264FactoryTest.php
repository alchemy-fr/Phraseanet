<?php

namespace Alchemy\Tests\Phrasea\Http\H264PseudoStreaming;

use Alchemy\Phrasea\Http\H264PseudoStreaming\H264Factory;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class H264FactoryTest extends \PhraseanetTestCase
{
    public function testFactoryCreation()
    {
        $factory = H264Factory::create(self::$DI['app']);
        $this->assertInstanceOf('Alchemy\Phrasea\Http\H264PseudoStreaming\H264Factory', $factory);
    }

    public function testFactoryWithH264Enable()
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $factory = new H264Factory($logger, true, 'nginx', $this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\H264PseudoStreaming\H264Interface', $factory->createMode());
        $this->assertTrue($factory->isH264Enabled());
    }

    public function testFactoryWithH264Disabled()
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $factory = new H264Factory($logger, false, 'nginx',$this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\H264PseudoStreaming\NullMode', $factory->createMode());
        $this->assertFalse($factory->isH264Enabled());
    }

    public function testFactoryWithWrongTypeThrowsAnExceptionIfRequired()
    {
        $this->expectException(\Alchemy\Phrasea\Exception\InvalidArgumentException::class);
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $factory = new H264Factory($logger, true, 'wrong-type', $this->getNginxMapping());
        $factory->createMode(true);
    }

    public function testFactoryWithWrongTypeDoesNotThrowsAnException()
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $logger->expects($this->once())
               ->method('error')
               ->with($this->isString());

        $factory = new H264Factory($logger, true, 'wrong-type', $this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\H264PseudoStreaming\NullMode', $factory->createMode(false));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideTypes')]
    public function testFactoryType($type, $mapping, $classmode)
    {
        $logger = $this->createMock('Psr\Log\LoggerInterface');

        $factory = new H264Factory($logger, true, $type, $mapping);
        $this->assertInstanceOf($classmode, $factory->createMode());
    }

    public static function provideTypes()
    {
        return [
            ['nginx', self::getNginxMapping(), 'Alchemy\Phrasea\Http\H264PseudoStreaming\Nginx'],
            ['apache', self::getNginxMapping(), 'Alchemy\Phrasea\Http\H264PseudoStreaming\Apache'],
            ['apache2', self::getNginxMapping(), 'Alchemy\Phrasea\Http\H264PseudoStreaming\Apache'],
        ];
    }

    private static function getNginxMapping()
    {
        return [[
            'directory'   =>  __DIR__ . '/../../../../files/',
            'mount-point' => '/protected/',
            'passphrase'  => 'dfdskqhfsfilddsmfmqsdmlfomqs',
        ]];
    }
}
