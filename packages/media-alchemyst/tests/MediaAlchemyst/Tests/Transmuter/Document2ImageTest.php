<?php

namespace MediaAlchemyst\Tests\Transmuter;

use MediaAlchemyst\Exception\SpecNotSupportedException;
use MediaAlchemyst\Transmuter\Document2Image;
use MediaAlchemyst\Tests\AbstractAlchemystTester;
use MediaAlchemyst\DriversContainer;
use MediaAlchemyst\Specification\Image;
use MediaAlchemyst\Specification\Video;
use Symfony\Component\Process\ExecutableFinder;

class Document2ImageTest extends AbstractAlchemystTester
{
    /**
     * @var Document2Image
     */
    protected $object;
    protected $specs;
    protected $source;
    protected $dest;

    protected function setUp(): void
    {
        $executableFinder = new ExecutableFinder();
        if (!$executableFinder->find('unoconv')) {
            $this->markTestSkipped('Unoconv is not installed');
        }

        $this->object = new Document2Image(new DriversContainer(), $this->getFsManager());

        $this->specs = new Image();
        $this->source = $this->getMediaVorus()->guess(__DIR__ . '/../../../files/Hello.odt');
        $this->dest = __DIR__ . '/../../../files/output.jpg';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        if (null !== $this->dest && file_exists($this->dest) && is_writable($this->dest)) {
            unlink($this->dest);
        }
    }

    public function testExecute()
    {
        $this->specs->setDimensions(320, 240);
        $this->specs->setResizeMode(Image::RESIZE_MODE_INBOUND);

        $this->object->execute($this->specs, $this->source, $this->dest);

        $MediaDest = $this->getMediaVorus()->guess($this->dest);

        $this->assertEquals(320, $MediaDest->getWidth());
        $this->assertEquals(240, $MediaDest->getHeight());
    }

    public function testExecuteWrongSpecs()
    {
        $this->expectException(SpecNotSupportedException::class);
        $this->specs = new Video();
        $this->object->execute($this->specs, $this->source, $this->dest);
    }
}
