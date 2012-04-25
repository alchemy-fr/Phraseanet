<?php

namespace Alchemy\Phrasea\Media\Subdef;

class ImageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Image
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Image;
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Image::getType
     */
    public function testGetType()
    {
        $this->assertEquals(Subdef::TYPE_IMAGE, $this->object->getType());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Image::getDescription
     */
    public function testGetDescription()
    {
        $this->assertTrue(is_string($this->object->getDescription()));
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Image::getMediaAlchemystSpec
     */
    public function testGetMediaAlchemystSpec()
    {
        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Image', $this->object->getMediaAlchemystSpec());
    }

}
