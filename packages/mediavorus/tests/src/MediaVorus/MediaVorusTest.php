<?php

namespace MediaVorus;

use MediaVorus\Media\MediaInterface;

class MediaVorusTest extends TestCase
{
    /**
     * @var MediaVorus
     */
    protected $object;

    protected function setUp(): void
    {
        parent::setUp();

        $this->object = $this->createMediaVorus();
    }

    public function testGuess()
    {
        $media = $this->object->guess(__DIR__ . '/../../files/ExifTool.jpg');
        $this->assertInstanceOf('\\MediaVorus\\Media\\MediaInterface', $media);
    }

    public function testGuessFromMimeType()
    {
        $media = $this->object->guess(__DIR__ . '/../../files/ExifTool.jpg');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Image', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/CanonRaw.cr2');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Image', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/APE.ape');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Audio', $media);

        $media = $this->object->guess(__DIR__ . '/../../files/PDF.pdf');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Document', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/ZIP.gz');
        $this->assertInstanceOf('\\MediaVorus\\Media\\DefaultMedia', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/Flash.swf');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Flash', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/Test.ogv');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Video', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/Hello.odt');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Document', $media);
        $media = $this->object->guess(__DIR__ . '/../../files/Unittestfile.indd');
        $this->assertInstanceOf('\\MediaVorus\\Media\\Document', $media);
    }

    public function testInspectDirectory()
    {
        $medias = $this->object->inspectDirectory(__DIR__ . '/../../files');
        $this->assertInstanceOf('\\MediaVorus\\MediaCollection', $medias);
        $this->assertEquals(24, count($medias));

        foreach ($medias as $media) {
            if ($media->getFile()->getFilename() === 'KyoceraRaw.raw') {
                continue;
            }
            if (in_array($media->getFile()->getFilename(), array('XMP.svg', 'Font.dfont'))) {
                continue;
            }
            if ($media->getType() === MediaInterface::TYPE_IMAGE) {
                $this->assertIsInt($media->getWidth(), sprintf('Test width of %s', $media->getFile()->getFilename()));
            }
        }
    }

    public function testCreate()
    {
        $this->requireExiftool();
        $this->createFFProbe();

        $mediavorus = MediaVorus::create(static::getExiftoolClassesDirectory());
        $this->assertInstanceOf('MediaVorus\\MediaVorus', $mediavorus);
        $this->assertInstanceOf('MediaVorus\\Media\\Image', $mediavorus->guess(__DIR__ . '/../../files/ExifTool.jpg'));
    }
}
