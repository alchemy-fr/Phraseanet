<?php

namespace MediaVorus\Media;

use MediaVorus\MediaVorus;
use MediaVorus\Media\MediaInterface;

class VideoTest extends MediaTestCase
{
    /**
     * @var Video
     */
    protected $object;
    protected $mediavorus;
    protected $file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mediavorus = $this->createMediaVorus();
        $this->file = __DIR__ . '/../../../files/videoFlashed.MOV';
        $this->object = $this->mediavorus->guess($this->file);
    }

    public function testGetType()
    {
        $this->assertEquals(MediaInterface::TYPE_VIDEO, $this->object->getType());
    }

    public function testGetWidth()
    {
        $this->assertTrue(is_int($this->object->getWidth()));
        $this->assertEquals(568, $this->object->getWidth());
    }

    public function testGetHeight()
    {
        $this->assertTrue(is_int($this->object->getHeight()));
        $this->assertEquals(320, $this->object->getHeight());
    }

    public function testGetDuration()
    {
        $this->assertTrue(is_float($this->object->getDuration()));
        $this->assertEquals(2, floor($this->object->getDuration()));
    }

    public function testGetFrameRate()
    {
        $this->assertTrue(is_float($this->object->getFrameRate()));
        $this->assertEquals(30.0, $this->object->getFrameRate());
    }

    public function testGetAudioSampleRate()
    {
        $this->assertTrue(is_int($this->object->getAudioSampleRate()));
        $this->assertEquals(44100, $this->object->getAudioSampleRate());
    }

    public function testGetVideoCodec()
    {
        $this->assertEquals('H.264', $this->object->getVideoCodec());
    }

    public function testGetAudioCodec()
    {
        $this->assertEquals('mp4a', $this->object->getAudioCodec());
    }

    public function testSerialize()
    {
        $json = $this->getSerializer()->serialize($this->object, 'json');

        $data = json_decode($json, true);

        $this->assertArrayNotHasKey('ffprobe', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('height', $data);
        $this->assertArrayHasKey('width', $data);
        $this->assertArrayHasKey('duration', $data);
    }
}
