<?php

namespace MediaVorus\Media;

use MediaVorus\File;
use MediaVorus\TestCase;

class DefaultMediaTest extends TestCase
{
    /**
     * @var DefaultMedia
     */
    protected $object;
    protected $GPSobject;

    protected function setUp(): void
    {
        parent::setUp();

        $reader = $this->createReader();
        $writer = $this->createWriter();

        $file = __DIR__ . '/../../../files/ExifTool.jpg';
        $this->object = new DefaultMedia(new File($file), $reader->reset()->files($file)->first(), $writer);

        $file = __DIR__ . '/../../../files/GPS.jpg';
        $this->GPSobject = new DefaultMedia(new File($file), $reader->reset()->files($file)->first(), $writer);
    }

    public function testGetHash()
    {
        $this->assertEquals('47684e05475e7591e15140449b12bd6e3e1c82c44a89f1803410e82051a2c88e', $this->object->getHash('sha256'));
    }

    public function testGetFile()
    {
        $this->assertInstanceOf('\MediaVorus\File', $this->object->getFile());
        $this->assertEquals('ExifTool.jpg', $this->object->getFile()->getFilename());
    }

    public function testGetLongitude()
    {
        $this->assertIsFloat($this->GPSobject->getLongitude());
        $this->assertEquals(-1.91416666666667, $this->GPSobject->getLongitude());
    }

    public function testGetLongitudeRef()
    {
        $this->assertFalse(in_array($this->GPSobject->getLongitudeRef(), array('W', 'E')));
    }

    public function testGetLatitude()
    {
        $this->assertIsFloat($this->GPSobject->getLatitude());
        $this->assertEquals(54.9896666666667, $this->GPSobject->getLatitude());
    }

    public function testGetLatitudeRef()
    {
        $this->assertFalse(in_array($this->GPSobject->getLatitudeRef(), array('N', 'S')));
    }
}

