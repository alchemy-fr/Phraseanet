<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Audio;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Tests\Tools\TranslatorMockTrait;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class AudioTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /**
     * @var Audio
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Audio($this->createTranslatorMock());
    }

    public function testGetType()
    {
        $this->assertEquals(Subdef::TYPE_AUDIO, $this->object->getType());
    }

    public function testGetDescription()
    {
        $this->assertTrue(is_string($this->object->getDescription()));
    }

    public function testGetMediaAlchemystSpec()
    {
        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Audio', $this->object->getMediaAlchemystSpec());
    }
}
