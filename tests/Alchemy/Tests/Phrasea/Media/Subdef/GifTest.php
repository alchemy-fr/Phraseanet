<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Gif;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Tests\Tools\TranslatorMockTrait;

class GifTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /**
     * @var Gif
     */
    protected $object;

    public function setUp()
    {
        $this->object = new Gif($this->createTranslatorMock());
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
