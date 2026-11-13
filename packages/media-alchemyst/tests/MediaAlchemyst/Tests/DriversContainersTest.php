<?php

namespace MediaAlchemyst\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use MediaAlchemyst\DriversContainer;
use Symfony\Component\Process\ExecutableFinder;

class DriversContainersTest extends TestCase
{
    public function testDrivers()
    {
        $object = new DriversContainer();
        $executableFinder = new ExecutableFinder();

        if ($executableFinder->find('ffmpeg') && $executableFinder->find('ffprobe')) {
            $this->assertInstanceOf('\\FFMpeg\\FFMpeg', $object['ffmpeg.ffmpeg']);
        }

        if (extension_loaded('gd') || extension_loaded('imagick') || extension_loaded('gmagick')) {
            $this->assertInstanceOf('\\Imagine\\Image\\ImagineInterface', $object['imagine']);
        }

        if ($executableFinder->find('unoconv')) {
            $this->assertInstanceOf('\\Unoconv\\Unoconv', $object['unoconv']);
        }

        if ($executableFinder->find('MP4Box')) {
            $this->assertInstanceOf('\\MP4Box\\MP4Box', $object['mp4box']);
        }

        $this->assertInstanceOf('\\PHPExiftool\\PreviewExtractor', $object['exiftool.preview-extractor']);
        $this->assertInstanceOf('\\Psr\\Log\\LoggerInterface', $object['logger']);
    }

    public function testCustomizedDrivers()
    {
        $object = new DriversContainer();

        $executableFinder = new ExecutableFinder();
        $php = $executableFinder->find('php');

        if (null === $php) {
            $this->markTestSkipped('Unable to find mandatory PHP executable');
        }

        if (!extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is required for this test');
        }

        $object['configuration'] = array(
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
        );

        $this->assertEquals($php, $object['ffmpeg.ffmpeg']->getFFMpegDriver()->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $object['ffmpeg.ffprobe']->getFFProbeDriver()->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $object['ghostscript.transcoder']->getProcessBuilderFactory()->getBinary());
        $this->assertInstanceOf('Imagine\Gd\Imagine', $object['imagine']);
        $this->assertEquals($php, $object['unoconv']->getProcessBuilderFactory()->getBinary());
        $this->assertEquals($php, $object['mp4box']->getProcessBuilderFactory()->getBinary());

        $this->assertEquals(42, $object['ffmpeg.ffmpeg']->getFFMpegDriver()->getConfiguration()->get('ffmpeg.threads'));
        $this->assertEquals(42, $object['ffmpeg.ffmpeg']->getFFMpegDriver()->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $object['ffmpeg.ffprobe']->getFFProbeDriver()->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $object['ghostscript.transcoder']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $object['unoconv']->getProcessBuilderFactory()->getTimeout());
        $this->assertEquals(42, $object['mp4box']->getProcessBuilderFactory()->getTimeout());
    }

    #[DataProvider('provideKeysToTest')]
    public function testInvalidDrivers($key1)
    {
        $object = new DriversContainer();
        $object['configuration'] = array(
            'ffmpeg.ffmpeg.binaries'       => '/path/to/nowhere',
            'ffmpeg.ffprobe.binaries'      => '/path/to/nowhere',
            'gs.binaries'                  => '/path/to/nowhere',
            'mp4box.binaries'              => '/path/to/nowhere',
            'unoconv.binaries'             => '/path/to/nowhere',
        );

        $this->expectException('MediaAlchemyst\Exception\RuntimeException');
        $object[$key1];
    }

    public static function provideKeysToTest()
    {
        return array(
            array('ffmpeg.ffmpeg'),
            array('ffmpeg.ffprobe'),
            array('ghostscript.transcoder'),
            array('unoconv'),
            array('mp4box'),
        );
    }
}
