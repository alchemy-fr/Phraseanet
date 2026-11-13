<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Image;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Tests\Tools\TranslatorMockTrait;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ImageTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /**
     * @var Image
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Image($this->createTranslatorMock());
    }

    public function testGetType()
    {
        $this->assertEquals(Subdef::TYPE_IMAGE, $this->object->getType());
    }

    public function testGetDescription()
    {
        $this->assertTrue(is_string($this->object->getDescription()));
    }

    public function testGetMediaAlchemystSpec()
    {
        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Image', $this->object->getMediaAlchemystSpec());
    }
}
