<?php

namespace MediaAlchemyst\Tests;

use MediaAlchemyst\Exception\FileNotFoundException;
use MediaAlchemyst\Exception\RuntimeException;
use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\Specification\Audio;
use MediaAlchemyst\Specification\Video;
use MediaAlchemyst\Specification\Image;

class AlchemystNoBinaryTest extends AbstractAlchemystTester
{
    protected $specsAudio;
    protected $specsImage;
    protected $specsVideo;

    protected function setUp(): void
    {
        $this->requireExiftool();

        $this->specsAudio = new Audio();
        $this->specsVideo = new Video();
        $this->specsVideo->setDimensions(320, 240);
        $this->specsImage = new Image();
        $this->specsImage->setDimensions(320, 240);
    }

    public function testOpenUnknownFile()
    {
        $this->expectException(FileNotFoundException::class);
        $driversContainer = new DriversContainer();
        $object = new Alchemyst($driversContainer, $this->getFsManager());

        $object->turnInto(__DIR__ . '/../../files/invalid.file', 'dest.mpg', $this->createStub('MediaAlchemyst\Specification\SpecificationInterface'));
    }

    public function testTurnIntoAudioAudio()
    {
        $this->expectException(RuntimeException::class);
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffmpeg.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());

        $dest = __DIR__ . '/../../files/output.flac';

        $object->turnInto(__DIR__ . '/../../files/Audio.mp3', $dest, $this->specsAudio);
    }

    public function testTurnIntoAudioAudioWithFFProbe()
    {
        $this->expectException(RuntimeException::class);
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffprobe.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());

        $dest = __DIR__ . '/../../files/output.flac';

        $object->turnInto(__DIR__ . '/../../files/Audio.mp3', $dest, $this->specsAudio);
    }


    public function testTurnIntoDocumentImage()
    {
        $this->expectException(RuntimeException::class);
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'unoconv.binaries'                  => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.png';

        $object->turnInto(__DIR__ . '/../../files/Hello.odt', $dest, $this->specsImage);
    }



    public function testTurnIntoVideoImage()
    {
        $this->expectException(RuntimeException::class);
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffmpeg.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.png';

        $object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsImage);
    }

    public function testTurnIntoVideoImageWithFFprobe()
    {
        $this->expectException(RuntimeException::class);
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffprobe.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.png';

        $object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsImage);
    }

    public function testTurnIntoVideoVideo()
    {
        $this->expectException(RuntimeException::class);
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffmpeg.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.webm';

        $object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsVideo);
    }

    public function testTurnIntoVideoVideoWithFFProbe()
    {
        $this->expectException(RuntimeException::class);
        $driversContainer = new DriversContainer();
        $driversContainer['configuration'] = array(
            'ffmpeg.ffprobe.binaries'       => 'nofile',
        );
        $object = new Alchemyst($driversContainer, $this->getFsManager());
        $dest = __DIR__ . '/../../files/output.webm';

        $object->turnInto(__DIR__ . '/../../files/Test.ogv', $dest, $this->specsVideo);
    }
}
