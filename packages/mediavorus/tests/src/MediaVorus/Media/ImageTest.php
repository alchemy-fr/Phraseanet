<?php

namespace MediaVorus\Media;

use MediaVorus\File;
use MediaVorus\MediaVorus;
use MediaVorus\Media\MediaInterface;

class ImageTest extends MediaTestCase
{
    /**
     * @var Image
     */
    protected $object;
    protected $mediavorus;
    protected $reader;
    protected $writer;
    protected $ffprobe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reader = $this->createReader();
        $this->writer = $this->createWriter();
        $file = __DIR__ . '/../../../files/ExifTool.jpg';

        $this->object = new Image(new File($file), $this->reader->reset()->files($file)->first(), $this->writer);

        $this->ffprobe = $this->createFFProbe();

        $this->mediavorus = new MediaVorus($this->reader, $this->writer, $this->ffprobe);
    }

    public function testGetType()
    {
        $this->assertEquals(MediaInterface::TYPE_IMAGE, $this->object->getType());
    }

    public function testIsrawImage()
    {
        $this->assertFalse($this->object->isRawImage());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/CanonRaw.cr2');
        $this->assertTrue($object->isRawImage());
    }

    public function testGetWidth()
    {
        $this->assertTrue(is_int($this->object->getWidth()));
        $this->assertEquals(8, $this->object->getWidth());

        $objects = $this->mediavorus->inspectDirectory(__DIR__ . '/../../../files/');
        foreach ($objects as $object) {
            if ($object->getType() == MediaInterface::TYPE_IMAGE) {

                if (in_array($object->getFile()->getFilename(), array('KyoceraRaw.raw', 'Font.dfont', 'XMP.svg'))) {
                    $this->assertNull($object->getWidth());
                } else {
                    $this->assertTrue(is_int($object->getWidth()), $object->getFile()->getFilename() . " has int width");
                }
            }
        }
    }

    public function testGetHeight()
    {
        $this->assertTrue(is_int($this->object->getHeight()));
        $this->assertEquals(8, $this->object->getHeight());

        $objects = $this->mediavorus->inspectDirectory(__DIR__ . '/../../../files/');
        foreach ($objects as $object) {
            if ($object->getType() == MediaInterface::TYPE_IMAGE) {

                if (in_array($object->getFile()->getFilename(), array('KyoceraRaw.raw', 'Font.dfont', 'XMP.svg'))) {
                    $this->assertNull($object->getHeight());
                } else {
                    $this->assertTrue(is_int($object->getHeight()), $object->getFile()->getFilename() . " has int width");
                }
            }
        }
    }

    public function testGetChannels()
    {
        $this->assertTrue(is_int($this->object->getChannels()));
        $this->assertEquals(3, $this->object->getChannels());
    }

    public function testGetFocalLength()
    {
        $this->assertTrue(is_float($this->object->getFocalLength()));
        $this->assertEquals(6.0, $this->object->getFocalLength());
    }

    public function testGetColorDepth()
    {
        $this->assertTrue(is_int($this->object->getColorDepth()));
        $this->assertEquals(8, $this->object->getColorDepth());
    }

    public function testGetCameraModel()
    {
        $this->assertTrue(is_string($this->object->getCameraModel()));
    }

    public function testGetFlashFired()
    {
        $this->assertTrue(is_bool($this->object->getFlashFired()));

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/photo01.JPG');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/CanonRaw.cr2');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/photoAutoNoFlash.jpg');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/PhotoFlash.jpg');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertTrue($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/videoFlashed.MOV');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertNull($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/XMP.xmp');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/DNG.dng');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());

        $object = $this->mediavorus->guess(__DIR__ . '/../../../files/Panasonic.rw2');
        $this->assertInstanceOf('\MediaVorus\Media\Image', $object);
        $this->assertFalse($object->getFlashFired());
    }

    public function testGetAperture()
    {
        $this->assertIsFloat($this->object->getAperture());
    }

    public function testGetShutterSpeed()
    {
        $this->assertIsFloat($this->object->getShutterSpeed());
    }

    public function testGetOrientation()
    {
        $object1 = $this->mediavorus->guess(__DIR__ . '/../../../files/photo01.JPG');
        $object2 = $this->mediavorus->guess(__DIR__ . '/../../../files/photo02.JPG');
        $object3 = $this->mediavorus->guess(__DIR__ . '/../../../files/photo03.JPG');
        $object4 = $this->mediavorus->guess(__DIR__ . '/../../../files/Test.ogv');

        $this->assertEquals(Image::ORIENTATION_0, $this->object->getOrientation());
        $this->assertEquals(Image::ORIENTATION_90, $object1->getOrientation());
        $this->assertEquals(Image::ORIENTATION_180, $object2->getOrientation());
        $this->assertEquals(Image::ORIENTATION_270, $object3->getOrientation());
        $this->assertEquals(Image::ORIENTATION_0, $object4->getOrientation());
    }

    public function testGetCreationDate()
    {
        $this->assertTrue(is_string($this->object->getCreationDate()));
    }

    public function testGetHyperfocalDistance()
    {
        $this->assertIsFloat($this->object->getHyperfocalDistance());
    }

    public function testGetISO()
    {
        $this->assertTrue(is_int($this->object->getISO()));
        $this->assertEquals(100, $this->object->getISO());
    }

    public function testGetLightValue()
    {
        $this->assertIsFloat($this->object->getLightValue());
    }

    public function testGetColorSpace()
    {
        $file = __DIR__ . '/../../../files/ExifTool.jpg';
        $media = new Image(new File($file), $this->reader->reset()->files($file)->first(), $this->writer);
        $this->assertEquals(Image::COLORSPACE_RGB, $media->getColorSpace());

        $file = __DIR__ . '/../../../files/GRAYSCALE.jpg';
        $media = new Image(new File($file), $this->reader->reset()->files($file)->first(), $this->writer);
        $this->assertEquals(Image::COLORSPACE_GRAYSCALE, $media->getColorSpace());

        $file = __DIR__ . '/../../../files/RVB.jpg';
        $media = new Image(new File($file), $this->reader->reset()->files($file)->first(), $this->writer);
        $this->assertEquals(Image::COLORSPACE_RGB, $media->getColorSpace());
    }

    public function testSerialize()
    {
        $json = $this->getSerializer()->serialize($this->object, 'json');

        $data = json_decode($json, true);

        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('height', $data);
        $this->assertArrayHasKey('width', $data);
    }
}
