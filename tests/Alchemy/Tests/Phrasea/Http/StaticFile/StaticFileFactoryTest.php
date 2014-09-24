<?php

namespace Alchemy\Tests\Phrasea\Http\XSendFile;

use Alchemy\Phrasea\Http\StaticFile\StaticFileFactory;

class StaticFileFactoryTest extends \PhraseanetPHPUnitAbstract
{
    public function testFactoryCreation()
    {
        $factory = StaticFileFactory::create(self::$DI['app']);
        $this->assertInstanceOf('Alchemy\Phrasea\Http\StaticFile\StaticFileFactory', $factory);
    }

    public function testFactoryWithStaticFileEnable()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $factory = new StaticFileFactory($logger, true, 'nginx', self::$DI['app']['phraseanet.thumb-symlinker']);
        $this->assertInstanceOf('Alchemy\Phrasea\Http\StaticFile\AbstractStaticMode', $factory->getMode());
    }

    public function testFactoryWithStaticFileDisabled()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $factory = new StaticFileFactory($logger, false, 'nginx', self::$DI['app']['phraseanet.thumb-symlinker']);
        $this->assertInstanceOf('Alchemy\Phrasea\Http\StaticFile\NullMode', $factory->getMode());
        $this->assertFalse($factory->isStaticFileModeEnabled());
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testFactoryWithWrongTypeThrowsAnExceptionIfRequired()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $factory = new StaticFileFactory($logger, true, 'wrong-type', self::$DI['app']['phraseanet.thumb-symlinker']);
        $factory->getMode(true);
    }

    public function testFactoryWithWrongTypeDoesNotThrowsAnException()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $logger->expects($this->once())
                ->method('error')
                ->with($this->isType('string'));

        $factory = new StaticFileFactory($logger, true, 'wrong-type', self::$DI['app']['phraseanet.thumb-symlinker']);
        $this->assertInstanceOf('Alchemy\Phrasea\Http\StaticFile\NullMode', $factory->getMode(false));
    }
}
