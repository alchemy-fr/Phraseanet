<?php

namespace MediaAlchemyst\Tests\Transmuter;

use MediaAlchemyst\Exception\SpecNotSupportedException;
use MediaAlchemyst\Transmuter\Audio2Audio;
use MediaAlchemyst\Tests\AbstractAlchemystTester;
use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Specification\Audio;
use MediaAlchemyst\Tests\Specification\UnknownSpecs;

class Audio2AudioTest extends AbstractAlchemystTester
{
    /**
     * @var Audio2Audio
     */
    protected $object;
    protected $specs;
    protected $source;
    protected $dest;

    protected function setUp(): void
    {
        $this->object = new Audio2Audio(new DriversContainer(), $this->getFsManager());
        $this->specs = new Audio();
        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/Audio.mp3');
        $this->dest = __DIR__ . '/../../../files/output_audio.flac';
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

        $this->assertContains($mediaDest->getFile()->getMimeType(), array('audio/flac', 'audio/x-flac'));
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
    }

    public function testExecuteWithOptions()
    {
        $this->specs->setAudioCodec('flac');
        $this->specs->setAudioSampleRate(96000);
        $this->specs->setAudioKiloBitrate(256);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertContains($mediaDest->getFile()->getMimeType(), array('audio/flac', 'audio/x-flac'));
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
    }

    public function testExecuteMp3Type()
    {
        $this->dest = __DIR__ . '/../../../files/output_audio.mp3';

        $this->object->execute($this->specs, $this->source, $this->dest);

        $mediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals('audio/mpeg', $mediaDest->getFile()->getMimeType());
        $this->assertEquals(round($this->source->getDuration()), round($mediaDest->getDuration()));
    }

    public function testWrongSpecs()
    {
        $this->expectException(SpecNotSupportedException::class);
        $this->object->execute(new UnknownSpecs(), $this->source, $this->dest);
    }
}
