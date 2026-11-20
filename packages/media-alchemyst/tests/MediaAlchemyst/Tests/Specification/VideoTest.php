<?php

namespace MediaAlchemyst\Tests\Specification;

use PHPUnit\Framework\TestCase;
use MediaAlchemyst\Specification\Video;
use MediaAlchemyst\Specification\SpecificationInterface;

class VideoTest extends TestCase
{
    /**
     * @var Video
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new Video;
    }

    public function testGetType()
    {
        $this->assertEquals(SpecificationInterface::TYPE_VIDEO, $this->object->getType());
    }

    public function testSetVideoCodec()
    {
        $this->object->setVideoCodec('Aubergine');
        $this->assertEquals('Aubergine', $this->object->getVideoCodec());
    }

    public function testSetDimensions()
    {
        $this->object->setDimensions(480, 220);
        $this->assertEquals(480, $this->object->getWidth());
        $this->assertEquals(220, $this->object->getHeight());
    }

    public function testSetGOPSize()
    {
        $this->object->setGOPSize(10);
        $this->assertEquals(10, $this->object->getGOPSize());
    }

    public function testSetFrameRate()
    {
        $this->object->setFramerate(10);
        $this->assertEquals(10, $this->object->getFramerate());
    }
}
