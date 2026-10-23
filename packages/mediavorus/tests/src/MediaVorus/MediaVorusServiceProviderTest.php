<?php

namespace MediaVorus;

use FFMpeg\FFProbe;
use MediaVorus\Exception\RuntimeException;
use Pimple\Container;

class MediaVorusServiceProviderTest extends TestCase
{
    private function getApplication()
    {
        $app = new Container();
        $app['exiftool.reader'] = function () {
            return $this->createReader();
        };
        $app['exiftool.writer'] = function () {
            return $this->createWriter();
        };

        return $app;
    }

    public function testInitializationWithFFProbe()
    {
        $ffprobe = $this->createFFProbe();

        $app = $this->getApplication();
        $app['ffmpeg.ffprobe'] = function () use ($ffprobe) {
            return $ffprobe;
        };
        $app->register(new MediaVorusServiceProvider());

        $this->assertInstanceOf('\\MediaVorus\\MediaVorus', $app['mediavorus']);
        $this->assertSame($app['mediavorus'], $app['mediavorus']);
        $this->assertSame($app['exiftool.reader'], $app['mediavorus']->getReader());
        $this->assertSame($app['exiftool.writer'], $app['mediavorus']->getWriter());
        $this->assertSame($app['ffmpeg.ffprobe'], $app['mediavorus']->getFFProbe());
    }

    public function testInitializationWithoutFFProbe()
    {
        $app = $this->getApplication();
        $app->register(new MediaVorusServiceProvider());

        $this->assertInstanceOf('\\MediaVorus\\MediaVorus', $app['mediavorus']);
        $this->assertSame($app['exiftool.reader'], $app['mediavorus']->getReader());
        $this->assertSame($app['exiftool.writer'], $app['mediavorus']->getWriter());
        $this->assertNull($app['mediavorus']->getFFProbe());
    }

    public function testInitializationWithNonWorkingFFProbe()
    {
        $app = $this->getApplication();
        $app['ffmpeg.ffprobe'] = function () {
            return FFProbe::create(array('ffprobe.binaries' => '/path/to/nowhere'));
        };
        $app->register(new MediaVorusServiceProvider());

        $this->assertInstanceOf('\\MediaVorus\\MediaVorus', $app['mediavorus']);
        $this->assertSame($app['exiftool.reader'], $app['mediavorus']->getReader());
        $this->assertSame($app['exiftool.writer'], $app['mediavorus']->getWriter());
        $this->assertNull($app['mediavorus']->getFFProbe());
    }

    public function testFailOnExiftool()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('MediaVorus Service Provider requires Exiftool Service Provider');

        $app = new Container();
        $app->register(new MediaVorusServiceProvider());

        $app['mediavorus'];
    }
}
