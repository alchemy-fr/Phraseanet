<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Gif;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Tests\Tools\TranslatorMockTrait;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class GifTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /**
     * @var Gif
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Gif($this->createTranslatorMock());
    }

    public function testGetType()
    {
        $this->assertEquals(Subdef::TYPE_ANIMATION, $this->object->getType());
    }

    public function testGetDescription()
    {
        $this->assertTrue(is_string($this->object->getDescription()));
    }

    public function testGetMediaAlchemystSpec()
    {
        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Animation', $this->object->getMediaAlchemystSpec());
    }
}
