<?php

namespace Unoconv\Tests;

use PHPUnit\Framework\TestCase;
use Unoconv\UnoconvServiceProvider;
use Pimple\Container;
use Symfony\Component\Process\ExecutableFinder;

class UnoconvServiceProviderTest extends TestCase
{
    private function getApplication()
    {
        return new Container();
    }

    public function testInit()
    {
        $finder = new ExecutableFinder();
        $unoconv = $finder->find('unoconv');

        if (null === $unoconv) {
            $this->markTestSkipped('Unable to detect unoconv, mandatory for this test');
        }

        $app = $this->getApplication();
        $app->register(new UnoconvServiceProvider(), array(
        ));

        $this->assertInstanceOf('\Unoconv\Unoconv', $app['unoconv']);
    }

    public function testInitWithCustomParameters()
    {
        $finder = new ExecutableFinder();
        $php = $finder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to detect unoconv, mandatory for this test');
        }

        $logger = $this->createStub('Psr\Log\LoggerInterface');

        $app = $this->getApplication();
        $app->register(new UnoconvServiceProvider(), array(
            'unoconv.configuration' => array(
                'unoconv.binaries' => $php,
                'timeout'          => 42,
            ),
            'unoconv.logger' => $logger,
        ));

        $this->assertInstanceOf('\Unoconv\Unoconv', $app['unoconv']);
        $this->assertEquals($php, $app['unoconv']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals(42, $app['unoconv']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals($logger, $app['unoconv']->getProcessRunner()->getLogger());
    }
}
