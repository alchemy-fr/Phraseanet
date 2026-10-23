<?php

namespace MediaAlchemyst\Tests;

use PHPUnit\Framework\TestCase;
use MediaAlchemyst\MediaAlchemystServiceProvider;
use Pimple\Container;
use Symfony\Component\Process\ExecutableFinder;

class MediaAlchemystServiceProviderTest extends TestCase
{
    public function testSimpleUsage()
    {
        $app = new Container();
        $app->register(new MediaAlchemystServiceProvider());

        $this->assertInstanceOf('MediaAlchemyst\Alchemyst', $app['media-alchemyst']);
        $this->assertSame($app['media-alchemyst'], $app['media-alchemyst']);
    }

    public function testCustomLogger()
    {
        $logger = $this->createStub('Psr\Log\LoggerInterface');

        $app = new Container();
        $app->register(new MediaAlchemystServiceProvider(), array(
            'media-alchemyst.logger' => $logger,
        ));

        $drivers = $app['media-alchemyst']->getDrivers();

        $this->assertSame($logger, $drivers['logger']);
    }

    public function testConfiguredUsage()
    {
        $executableFinder = new ExecutableFinder();
        $php = $executableFinder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find mandatory PHP executable');
        }

        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for this test');
        }

        $app = new Container();
        $app->register(new MediaAlchemystServiceProvider(), array(
            'media-alchemyst.configuration' => array(
                'ffmpeg.threads'               => 42,
                'ffmpeg.ffmpeg.timeout'        => 42,
                'ffmpeg.ffprobe.timeout'       => 42,
                'ffmpeg.ffmpeg.binaries'       => $php,
                'ffmpeg.ffprobe.binaries'      => $php,
                'imagine.driver'               => 'gd',
                'gs.timeout'                   => 42,
                'gs.binaries'                  => $php,
                'mp4box.timeout'               => 42,
                'mp4box.binaries'              => $php,
                'unoconv.binaries'             => $php,
                'unoconv.timeout'              => 42,
            )
        ));

        $drivers = $app['media-alchemyst']->getDrivers();

        $this->assertEquals($php, $drivers['ffmpeg.ffmpeg']->getFFMpegDriver()->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $drivers['ffmpeg.ffprobe']->getFFProbeDriver()->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $drivers['ghostscript.transcoder']->getProcessBuilderFactory()->getBinary());
        $this->assertInstanceOf('Imagine\Gd\Imagine', $drivers['imagine']);
        $this->assertEquals($php, $drivers['unoconv']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $drivers['mp4box']->getProcessBuilderFactory()->getBinary());

        $this->assertEquals(42, $drivers['ffmpeg.ffmpeg']->getFFMpegDriver()->getConfiguration()->get('ffmpeg.threads'));
        $this->assertEquals(42, $drivers['ffmpeg.ffmpeg']->getFFMpegDriver()->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $drivers['ffmpeg.ffprobe']->getFFProbeDriver()->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $drivers['ghostscript.transcoder']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $drivers['unoconv']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $drivers['mp4box']->getProcessBuilderFactory()->getTimeout());
    }
}
