<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Image;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Tests\Tools\TranslatorMockTrait;

class ImageTest extends \PHPUnit_Framework_TestCase
{
    use TranslatorMockTrait;

    /**
     * @var Image
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Image($this->createTranslatorMock());
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
