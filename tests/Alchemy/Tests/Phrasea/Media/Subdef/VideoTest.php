<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Video;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Tests\Tools\TranslatorMockTrait;

class VideoTest extends \PHPUnit_Framework_TestCase
{
    use TranslatorMockTrait;

    /**
     * @var Video
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Video($this->createTranslatorMock());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Video::getType
     */
    public function testGetType()
    {
        $this->assertEquals(Subdef::TYPE_VIDEO, $this->object->getType());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Video::getDescription
     */
    public function testGetDescription()
    {
        $this->assertTrue(is_string($this->object->getDescription()));
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Video::getMediaAlchemystSpec
     */
    public function testGetMediaAlchemystSpec()
    {
        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Video', $this->object->getMediaAlchemystSpec());
    }
}
