<?php

namespace MediaAlchemyst\Tests\Transmuter;

use MediaAlchemyst\Exception\SpecNotSupportedException;
use MediaAlchemyst\Exception\FormatNotSupportedException;
use PHPUnit\Framework\Attributes\DataProvider;
use MediaAlchemyst\Transmuter\Video2Video;
use MediaAlchemyst\Tests\AbstractAlchemystTester;
use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Tests\Specification\UnknownSpecs;
use MediaAlchemyst\Specification\Video;

class Video2VideoTest extends AbstractAlchemystTester
{
    /**
     * @var Video2Video
     */
    protected $object;
    protected $specs;
    protected $source;
    protected $dest;
    protected $mediavorus;

    protected function setUp(): void
    {
        $this->object = new Video2Video(new DriversContainer(), $this->getFsManager());

        $this->specs = new Video();
        $this->specs->setDimensions(320, 240);
        $this->specs->setResizeMode(Video::RESIZE_MODE_FIT);

        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/Test.ogv');
        $this->dest = __DIR__ . '/../../../files/output_video.webm';
    }

    protected function tearDown(): void
    {
        if (null !== $this->dest && file_exists($this->dest) && is_writable($this->dest)) {
            unlink($this->dest);
        }
    }

    public function testExecute()
    {
        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('video/webm', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
        $this->assertEquals(320, $mediaDest->getWidth());
        $this->assertEquals(240, $mediaDest->getHeight());
    }

    public function testExecuteInset()
    {
        $this->specs->setDimensions(320, 240);
        $this->specs->setResizeMode(Video::RESIZE_MODE_INSET);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('video/webm', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
        $this->assertLessThanOrEqual(320, $mediaDest->getWidth());
        $this->assertLessThanOrEqual(240, $mediaDest->getHeight());
    }

    public function testExecuteMP4()
    {
        $this->dest = __DIR__ . '/../../../files/output_video.mp4';

        $this->specs->setAudioCodec('libmp3lame');
        $this->specs->setVideoCodec('libx264');
        $this->specs->setAudioSampleRate(44100);
        $this->specs->setKiloBitrate(1000);
        $this->specs->setGOPSize(10);
        $this->specs->setFramerate(5);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('video/mp4', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
        $this->assertEquals(320, $mediaDest->getWidth());
        $this->assertEquals(240, $mediaDest->getHeight());
    }

    public function testExecuteWithOptions()
    {
        $this->specs->setAudioCodec('libvorbis');
        $this->specs->setVideoCodec('libvpx');
        $this->specs->setAudioSampleRate(44100);
        $this->specs->setKiloBitrate(1000);
        $this->specs->setGOPSize(10);
        $this->specs->setFramerate(5);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('video/webm', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
        $this->assertEquals(320, $mediaDest->getWidth());
        $this->assertEquals(240, $mediaDest->getHeight());
    }

    public function testExecuteWithBasSpecs()
    {
        $this->expectException(SpecNotSupportedException::class);
        $this->object->execute(new UnknownSpecs(), $this->source, $this->dest);
    }

    #[DataProvider('getFormats')]
    public function testGetFormatFromFileType($file, $instance)
    {
        $Object = new Video2VideoExtended();
        $this->assertInstanceOf($instance, $Object->testgetFormatFromFileType($file, 200, 200));
    }

    public static function getFormats()
    {
        return array(
          array('file.ogv', '\\FFMpeg\\Format\\Video\\Ogg'),
          array('file.mp4', '\\FFMpeg\\Format\\Video\\X264'),
          array('file.webm', '\\FFMpeg\\Format\\Video\\WebM'),
        );
    }

    public function testGetFormatFromWrongFileType()
    {
        $this->expectException(FormatNotSupportedException::class);
        $Object = new Video2VideoExtended();

        $Object->testgetFormatFromFileType('out.jpg', 200, 200);
    }
}

class Video2VideoExtended extends Video2Video
{
    public function __construct()
    {

    }

    public function testgetFormatFromFileType($dest, $width, $height)
    {
        return parent::getFormatFromFileType($dest, $width, $height);
    }
}
