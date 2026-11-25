<?php

namespace MP4Box\Tests;

use PHPUnit\Framework\TestCase;
use Alchemy\BinaryDriver\Exception\ExecutableNotFoundException;
use Pimple\Container;
use Symfony\Component\Process\ExecutableFinder;
use MP4Box\MP4BoxServiceProvider;

class MP4BoxServiceProviderTest extends TestCase
{
    public function getApplication()
    {
        return new Container();
    }

    private function requireMP4Box()
    {
        $finder = new ExecutableFinder();

        if (null === $finder->find('MP4Box')) {
            $this->markTestSkipped('Unable to detect MP4Box, required for this test');
        }
    }

    public function testInit()
    {
        $this->requireMP4Box();

        $app = $this->getApplication();
        $app->register(new MP4BoxServiceProvider());

        $this->assertInstanceOf('\\MP4Box\\MP4Box', $app['mp4box']);
    }

    public function testInitFailOnBinary()
    {
        $this->expectException(ExecutableNotFoundException::class);
        $app = $this->getApplication();
        $app->register(new MP4BoxServiceProvider(), array(
            'mp4box.configuration' => array(
                'mp4box.binaries' => 'no/binary/here'
            )
        ));

        $app['mp4box'];
    }

    public function testInitCustomLogger()
    {
        $this->requireMP4Box();

        $logger = $this->createStub('Psr\Log\LoggerInterface');

        $app = $this->getApplication();
        $app->register(new MP4BoxServiceProvider(), array(
            'mp4box.logger' => $logger
        ));

        $this->assertInstanceOf('\\MP4Box\\MP4Box', $app['mp4box']);
        $this->assertEquals($logger, $app['mp4box']->getProcessRunner()->getLogger());
    }

    public function testInitCustomTimeout()
    {
        $this->requireMP4Box();

        $app = $this->getApplication();
        $app->register(new MP4BoxServiceProvider(), array(
            'mp4box.configuration' => array(
                'timeout' => 128
            )
        ));

        $this->assertEquals(128, $app['mp4box']->getProcessBuilderFactory()->getTimeout());
    }
}
