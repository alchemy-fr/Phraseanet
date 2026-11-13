<?php

namespace MediaAlchemyst\Tests\Transmuter;

use MediaAlchemyst\Exception\SpecNotSupportedException;
use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use MediaAlchemyst\Transmuter\Image2Image;
use PHPUnit\Framework\Attributes\DataProvider;
use MediaAlchemyst\Tests\AbstractAlchemystTester;
use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Tests\Specification\UnknownSpecs;
use MediaAlchemyst\Specification\Image;

class Image2ImageTest extends AbstractAlchemystTester
{
    /**
     * @var Image2Image
     */
    protected $object;
    protected $specs;
    protected $source;
    protected $dest;

    protected function setUp(): void
    {
        $this->object = new Image2Image(new DriversContainer(), $this->getFsManager());

        Image2Image::$autorotate = false;
        Image2Image::$lookForEmbeddedPreview = false;

        $this->specs = new Image();
        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/photo03.JPG');
        $this->dest = __DIR__ . '/../../../files/output_auto_rotate.jpg';
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

       $MediaDest = $this->getMediaVorus()->guess($this->dest);

       $this->assertEquals($this->source->getWidth(), $MediaDest->getWidth());
       $this->assertEquals($this->source->getHeight(), $MediaDest->getHeight());
   }

   public function testExecuteOverCR2()
   {
       $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/test001.CR2');
       $this->object->execute($this->specs, $this->source, $this->dest);

       $MediaDest = $this->getMediaVorus()->guess($this->dest);
    
       // RAW decoders may crop a few border pixels
       $this->assertEqualsWithDelta(1876, $MediaDest->getHeight(), 8);
       $this->assertEqualsWithDelta(2808, $MediaDest->getWidth(), 8);
   }

    public function testExecuteOverAI()
    {
        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/plane.ai');
        $this->object->execute($this->specs, $this->source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals(842, $MediaDest->getHeight());
        $this->assertEquals(595, $MediaDest->getWidth());
    }

   public function testExecuteAutorotate()
   {
       Image2Image::$autorotate = true;

       $this->object->execute($this->specs, $this->source, $this->dest);

       $MediaDest = $this->getMediaVorus()->guess($this->dest);

       $this->assertEquals($this->source->getWidth(), $MediaDest->getHeight());
       $this->assertEquals($this->source->getHeight(), $MediaDest->getWidth());
   }

   public function testExecutePreviewExtract()
   {
       Image2Image::$lookForEmbeddedPreview = true;

       $source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/ExifTool.jpg');

       $this->object->execute($this->specs, $source, $this->dest);

       $MediaDest = $this->getMediaVorus()->guess($this->dest);

       $this->assertEquals(192, $MediaDest->getHeight());
       $this->assertEquals(288, $MediaDest->getWidth());
   }

    public function testExecuteSimpleResize()
    {
        $this->specs->setDimensions(320, 240);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertTrue($MediaDest->getHeight() <= 240);
        $this->assertTrue($MediaDest->getWidth() <= 320);
    }

    public function testExecuteOutBoundResize()
    {
        $this->specs->setDimensions(240, 260);
        $this->specs->setStrip(true);
        $this->specs->setRotationAngle(-90);
        $this->specs->setResizeMode(Image::RESIZE_MODE_OUTBOUND);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals(240, $MediaDest->getHeight());
        $this->assertEquals(260, $MediaDest->getWidth());
   }

    public function testExecuteInSetFixedRatio()
    {
        $this->specs->setDimensions(200, 200);
        $this->specs->setResizeMode(Image::RESIZE_MODE_INBOUND_FIXEDRATIO);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertTrue(200 >= $MediaDest->getHeight());
        $this->assertTrue(200 >= $MediaDest->getWidth());

        $this->assertEquals(round($this->source->getWidth() / $this->source->getHeight()), round($MediaDest->getWidth() / $MediaDest->getHeight()));
    }

    public function testWrongSpecs()
    {
        $this->expectException(SpecNotSupportedException::class);
        $this->object->execute(new UnknownSpecs(), $this->source, $this->dest);
    }

    public function testExecuteRawImage()
    {
        Image2Image::$lookForEmbeddedPreview = true;

        $source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/RAW_CANON_40D_RAW_V105.cr2');
        $this->object->execute($this->specs, $source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals(1936, $MediaDest->getWidth());
        $this->assertEquals(1288, $MediaDest->getHeight());
   }

   public function testWithMultiLayer()
   {
        $source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/multi-layer.psd');
        $this->object->execute($this->specs, $source, $this->dest);

        $this->assertFileExists($this->dest);
   }

    #[DataProvider('provideAlphaRemovalSpecs')]
    public function testAlphaRemoval($backgroundColor, $flatten, $expectedColor)
    {
        $drivers = new DriversContainer();
        $imagine = $drivers['imagine'];
        $palette = new RGB();

        $source = sys_get_temp_dir() . '/' . uniqid('alpha', true) . '.png';
        $dest = sys_get_temp_dir() . '/' . uniqid('alpha-dest', true) . '.png';

        try {
            $imagine->create(new Box(20, 20), $palette->color('#000000', 0))->save($source);

            $this->specs->setBackgroundColor($backgroundColor);
            $this->specs->setFlatten($flatten);

            $this->object->execute($this->specs, $this->getMediaVorus()->guess($source), $dest);

            $color = $imagine->open($dest)->getColorAt(new Point(10, 10));

            $this->assertEquals($expectedColor, (string) $color);
            $this->assertTrue($color->isOpaque());
        } finally {
            @unlink($source);
            @unlink($dest);
        }
    }

    public static function provideAlphaRemovalSpecs()
    {
        return array(
            array('#FF0000', false, '#ff0000'),
            array(null, true, '#ffffff'),
            array('#00FF00', true, '#00ff00'),
        );
    }
}
