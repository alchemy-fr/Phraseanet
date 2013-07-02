<?php

namespace Alchemy\Tests\Phrasea\Http\XSendFile;

use Alchemy\Phrasea\Http\XSendFile\XSendFileFactory;

class XSendFileFactoryTest extends \PhraseanetPHPUnitAbstract
{
    public function testFactoryCreation()
    {
        $factory = XSendFileFactory::create(self::$DI['app']);
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\XSendFileFactory', $factory);
    }

    public function testFactoryWithXsendFileEnable()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $factory = new XSendFileFactory($logger, true, 'nginx', $this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\ModeInterface', $factory->getMode());
    }

    public function testFactoryWithXsendFileDisabled()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $factory = new XSendFileFactory($logger, false, 'nginx',$this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\NullMode', $factory->getMode());
        $this->assertFalse($factory->isXSendFileModeEnabled());
    }

    /**
     * @expectedException Alchemy\Phrasea\Exception\InvalidArgumentException
     */
    public function testFactoryWithWrongTypeThrowsAnExceptionIfRequired()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $factory = new XSendFileFactory($logger, true, 'wrong-type', $this->getNginxMapping());
        $factory->getMode(true);
    }

    public function testFactoryWithWrongTypeDoesNotThrowsAnExceptio()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $logger->expects($this->once())
                ->method('error')
                ->with($this->isType('string'));

        $factory = new XSendFileFactory($logger, true, 'wrong-type', $this->getNginxMapping());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\NullMode', $factory->getMode(false));
    }

     /**
     * @dataProvider provideTypes
     */
    public function testFactoryType($type, $mapping, $classmode)
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $factory = new XSendFileFactory($logger, true, $type, $mapping);
        $this->assertInstanceOf($classmode, $factory->getMode());
    }

    public function provideTypes()
    {
        return array(
            array('apache', $this->getApacheMapping(), 'Alchemy\Phrasea\Http\XSendFile\ApacheMode'),
            array('apache2', $this->getApacheMapping(), 'Alchemy\Phrasea\Http\XSendFile\ApacheMode'),
            array('xsendfile', $this->getApacheMapping(), 'Alchemy\Phrasea\Http\XSendFile\ApacheMode'),
            array('nginx',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'),
            array('sendfile',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'),
            array('xaccel',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'),
            array('xaccelredirect',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'),
            array('x-accel',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'),
            array('x-accel-redirect',$this->getNginxMapping(), 'Alchemy\Phrasea\Http\XSendFile\NginxMode'),
        );
    }

    public function testInvalidMappingThrowsAnExceptionIfRequired()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $logger->expects($this->once())
                ->method('error')
                ->with($this->isType('string'));

        $factory = new XSendFileFactory($logger, true, 'nginx', array());
        $this->setExpectedException('Alchemy\Phrasea\Exception\RuntimeException');
        $factory->getMode(true);
    }

    public function testInvalidMappingDoesNotThrowsAnException()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $logger->expects($this->once())
                ->method('error')
                ->with($this->isType('string'));

        $factory = new XSendFileFactory($logger, true, 'nginx', array());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\NginxMode', $factory->getMode(false));
    }

    public function testInvalidMappingDoesNotThrowsAnExceptionByDefault()
    {
        $logger = $this->getMock('Psr\Log\LoggerInterface');

        $logger->expects($this->once())
                ->method('error')
                ->with($this->isType('string'));

        $factory = new XSendFileFactory($logger, true, 'nginx', array());
        $this->assertInstanceOf('Alchemy\Phrasea\Http\XSendFile\NginxMode', $factory->getMode());
    }

    private function getNginxMapping()
    {
        return array(array(
            'directory' =>  __DIR__ . '/../../../../files/',
            'mount-point' => '/protected/'
        ));
    }

    private function getApacheMapping()
    {
        return array(array(
            'directory' =>  __DIR__ . '/../../../../files/',
        ));
    }
}
