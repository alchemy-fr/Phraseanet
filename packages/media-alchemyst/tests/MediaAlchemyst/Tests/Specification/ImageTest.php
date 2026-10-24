<?php

namespace MediaAlchemyst\Tests\Specification;

use PHPUnit\Framework\TestCase;
use MediaAlchemyst\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\SpecificationInterface;

class ImageTest extends TestCase
{
    /**
     * @var Image
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new Image();
    }

    public function testGetType()
    {
        $this->assertEquals(SpecificationInterface::TYPE_IMAGE, $this->object->getType());
    }

    public function testCodec()
    {
        $this->object->setImageCodec("a_codec");
        $this->assertEquals("a_codec", $this->object->getImageCodec());
    }

    public function testSetDimensions()
    {
        $this->object->setDimensions(320, 240);
        $this->assertEquals(320, $this->object->getWidth());
        $this->assertEquals(240, $this->object->getHeight());
    }

    public function testSetResizeMode()
    {
        $this->assertEquals(Image::RESIZE_MODE_INBOUND_FIXEDRATIO, $this->object->getResizeMode());
        $this->object->setResizeMode(Image::RESIZE_MODE_OUTBOUND);
        $this->assertEquals(Image::RESIZE_MODE_OUTBOUND, $this->object->getResizeMode());
    }

    public function testSetResizeModeFail()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->object->setResizeMode('+Agauche');
    }

    public function testSetRotationAngle()
    {
        $this->object->setRotationAngle(90);
        $this->assertEquals(90, $this->object->getRotationAngle());
    }

    public function testSetQuality()
    {
        $this->object->setQuality(60);
        $this->assertEquals(60, $this->object->getQuality());
    }

    public function testSetResolution()
    {
        $this->object->setResolution(60, 80, Image::RESOLUTION_PIXELPERINCH);
        $this->assertEquals(60, $this->object->getResolutionX());
        $this->assertEquals(80, $this->object->getResolutionY());
        $this->assertEquals(Image::RESOLUTION_PIXELPERINCH, $this->object->getResolutionUnit());

        $this->object->setResolution(70, 90, Image::RESOLUTION_PIXELPERCENTIMETER);
        $this->assertEquals(70, $this->object->getResolutionX());
        $this->assertEquals(90, $this->object->getResolutionY());
        $this->assertEquals(Image::RESOLUTION_PIXELPERCENTIMETER, $this->object->getResolutionUnit());
    }

    #[DataProvider('getWrongResolutions')]
    public function testSetWrongResolution($res_x, $res_y, $res_unit)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->object->setResolution($res_x, $res_y, $res_unit);
    }

    public static function getWrongResolutions()
    {
        return array(
          array(10, 20, 'pixelparpied'),
          array(0, 20, Image::RESOLUTION_PIXELPERINCH),
          array(-5, 20, Image::RESOLUTION_PIXELPERINCH),
          array(10, 0, Image::RESOLUTION_PIXELPERINCH),
          array(10, -5, Image::RESOLUTION_PIXELPERINCH),
        );
    }

    public function testSetWrongQuality()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->object->setQuality(160);
    }

    public function testSetStrip()
    {
        $this->object->setStrip(true);
        $this->assertEquals(true, $this->object->getStrip());
        $this->object->setStrip(false);
        $this->assertEquals(false, $this->object->getStrip());
    }

    public function testSetFlatten()
    {
        $this->object->setFlatten(true);
        $this->assertEquals(true, $this->object->isFlatten());
        $this->object->setFlatten(false);
        $this->assertEquals(false, $this->object->isFlatten());
    }

    public function testFromPage()
    {
        $this->object->fromPage(2);
        $this->assertEquals(2, $this->object->getPage());
    }
}
