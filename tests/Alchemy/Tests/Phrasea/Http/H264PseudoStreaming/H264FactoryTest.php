<?php

namespace Alchemy\Tests\Phrasea\Http\H264PseudoStreaming;

use Alchemy\Phrasea\Http\H264PseudoStreaming\H264Factory;

class H264FactoryTest extends \PhraseanetTestCase
{
    public function testFactoryCreation()
    {
        $factory = H264Factory::create(self::$DI['app']);
        $this->assertInstanceOf('Alchemy\Phrasea\Http\H264PseudoStreaming\H264Factory', $factory);
    }

    public function testFactoryWithH264Enable()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $factory = new H264Factory($logger, true, 'nginx', $this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\H264PseudoStreaming\H264Interface', $factory->createMode());
        $this->assertTrue($factory->isH264Enabled());
    }

    public function testFactoryWithH264Disabled()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $factory = new H264Factory($logger, false, 'nginx',$this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\H264PseudoStreaming\NullMode', $factory->createMode());
        $this->assertFalse($factory->isH264Enabled());
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testFactoryWithWrongTypeThrowsAnExceptionIfRequired()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $factory = new H264Factory($logger, true, 'wrong-type', $this->getNginxMapping());
        $factory->createMode(true);
    }

    public function testFactoryWithWrongTypeDoesNotThrowsAnException()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $logger->expects($this->once())
               ->method('error')
               ->with($this->isType('string'));

        $factory = new H264Factory($logger, true, 'wrong-type', $this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\H264PseudoStreaming\NullMode', $factory->createMode(false));
    }

    /**
     * @dataProvider provideTypes
     */
    public function testFactoryType($type, $mapping, $classmode)
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $factory = new H264Factory($logger, true, $type, $mapping);
        $this->assertInstanceOf($classmode, $factory->createMode());
    }

    public function provideTypes()
    {
        return array(
            array('nginx', $this->getNginxMapping(), 'Alchemy\Phrasea\Http\H264PseudoStreaming\Nginx'),
            array('apache', $this->getNginxMapping(), 'Alchemy\Phrasea\Http\H264PseudoStreaming\Apache'),
            array('apache2', $this->getNginxMapping(), 'Alchemy\Phrasea\Http\H264PseudoStreaming\Apache'),
        );
    }

    private function getNginxMapping()
    {
        return array(array(
            'directory'   =>  __DIR__ . '/../../../../files/',
            'mount-point' => '/protected/',
            'passphrase'  => 'dfdskqhfsfilddsmfmqsdmlfomqs',
        ));
    }
}
