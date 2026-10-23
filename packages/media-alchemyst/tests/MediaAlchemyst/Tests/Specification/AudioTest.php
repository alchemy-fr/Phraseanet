<?php

namespace MediaAlchemyst\Tests\Specification;

use PHPUnit\Framework\TestCase;
use MediaAlchemyst\Specification\Audio;
use MediaAlchemyst\Specification\SpecificationInterface;

class AudioTest extends TestCase
{
    protected $object;

    protected function setUp(): void
    {
        $this->object = new Audio();
    }

    public function testGetType()
    {
        $this->assertEquals(SpecificationInterface::TYPE_AUDIO, $this->object->getType());
    }

    public function testSetAudioKiloBitrate()
    {
        $this->object->setAudioKiloBitrate(200);
        $this->assertEquals(200, $this->object->getAudioKiloBitrate());
    }

    public function testSetAudioCodec()
    {
        $this->object->setAudioCodec('Carlos');
        $this->assertEquals('Carlos', $this->object->getAudioCodec());
    }

    public function testSetAudioSampleRate()
    {
        $this->object->setAudioSampleRate(22050);
        $this->assertEquals(22050, $this->object->getAudioSampleRate());
    }

    public function testSetAudioChannels()
    {
        $this->object->setAudioChannels(4);
        $this->assertEquals(4, $this->object->getAudioChannels());
    }
}
