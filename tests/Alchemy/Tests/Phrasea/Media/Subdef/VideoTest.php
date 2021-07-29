<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Phrasea\Media\Subdef\Video;
use Alchemy\Tests\Tools\TranslatorMockTrait;
use MediaAlchemyst\Specification\Video as VideoSpecification;

/**
 * @group functional
 * @group legacy
 */
class VideoTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /**
     * @var Video
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
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
        $this->assertInstanceOf(VideoSpecification::class, $this->object->getMediaAlchemystSpec());
    }
}
