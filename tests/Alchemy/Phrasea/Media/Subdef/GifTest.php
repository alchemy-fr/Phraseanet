<?php

namespace Alchemy\Phrasea\Media\Subdef;

class GifTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Gif
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Gif;
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Gif::getType
     */
    public function testGetType()
    {
        $this->assertEquals(Subdef::TYPE_ANIMATION, $this->object->getType());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Gif::getDescription
     */
    public function testGetDescription()
    {
        $this->assertTrue(is_string($this->object->getDescription()));
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Gif::getMediaAlchemystSpec
     */
    public function testGetMediaAlchemystSpec()
    {
        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Animation', $this->object->getMediaAlchemystSpec());
    }

}
