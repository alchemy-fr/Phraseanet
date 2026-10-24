<?php

namespace MediaAlchemyst\Tests;

use MediaAlchemyst\Exception\FileNotFoundException;
use MediaAlchemyst\Exception\RuntimeException;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Specification\Audio;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\Video;
use MediaVorus\Media\MediaInterface;
use Symfony\Component\Process\ExecutableFinder;

class AlchemystTest extends AbstractAlchemystTester
{
    /**
     * @var Alchemyst
     */
    protected $object;
    protected $specsAudio;
    protected $specsImage;
    protected $specsVideo;

    protected function setUp(): void
    {
        $this->requireExiftool();

        $this->object = Alchemyst::create();

        $this->specsAudio = new Audio();
        $this->specsVideo = new Video();
        $this->specsVideo->setDimensions(320, 240);
        $this->specsImage = new Image();
        $this->specsImage->setDimensions(320, 240);
    }

    public function testOpenUnknownFile()
    {
        $this->expectException(FileNotFoundException::class);
        $this->object->turnInto(__DIR__ . '/../../files/invalid.file', 'here.mpg', $this->createStub('MediaAlchemyst\Specification\SpecificationInterface'));
    }

    public function testTurnIntoAudioAudio()
    {
        $dest = __DIR__ . '/../../files/output.flac';

        $this->object->turnInto(__DIR__ . '/../../files/Audio.mp3', $dest, $this->specsAudio);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_AUDIO, $media->getType());

        unlink($dest);
    }

    public function testTurnIntoFlashImageIsNotSupported()
    {
        $this->getMediaVorus();

        $this->expectException(RuntimeException::class);
        $this->object->turnInto(__DIR__ . '/../../files/flashfile.swf', __DIR__ . '/../../files/output.png', $this->specsImage);
    }

    public function testTurnIntoDocumentImage()
    {
        $executableFinder = new ExecutableFinder();
        if ( ! $executableFinder->find('unoconv')) {
            $this->markTestSkipped('Unoconv is not installed');
        }

        $dest = __DIR__ . '/../../files/output.png';

        $this->object->turnInto(__DIR__ . '/../../files/Hello.odt', $dest, $this->specsImage);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_IMAGE, $media->getType());

        unlink($dest);
    }


    public function testTurnIntoImageImage()
    {
        $dest = __DIR__ . '/../../files/output.png';

        $this->object->turnInto(__DIR__ . '/../../files/photo03.JPG', $dest, $this->specsImage);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_IMAGE, $media->getType());

        unlink($dest);
    }

    public function testTurnIntoVideoImage()
    {
        $dest = __DIR__ . '/../../files/output.png';

        $this->object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsImage);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_IMAGE, $media->getType());

        unlink($dest);
    }

    public function testTurnIntoVideoVideo()
    {
        $dest = __DIR__ . '/../../files/output.webm';

        $this->object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsVideo);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_VIDEO, $media->getType());

        unlink($dest);
    }

    public function testTurnIntoVideoAudio()
    {
        $dest = __DIR__ . '/../../files/output.wav';

        $this->object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsAudio);

        $media = $this->getMediaVorus()->guess($dest);
        $this->assertEquals(MediaInterface::TYPE_AUDIO, $media->getType());

        unlink($dest);
    }
}
