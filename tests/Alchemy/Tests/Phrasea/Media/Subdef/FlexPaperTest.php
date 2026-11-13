<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\FlexPaper;
use Alchemy\Phrasea\Media\Subdef\Specification\FlexPaperSpecification;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Tests\Tools\TranslatorMockTrait;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FlexPaperTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /**
     * @var FlexPaper
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new FlexPaper($this->createTranslatorMock());
    }

    public function testGetType()
    {
        $this->assertEquals(Subdef::TYPE_FLEXPAPER, $this->object->getType());
    }

    public function testGetDescription()
    {
        $this->assertTrue(is_string($this->object->getDescription()));
    }

    public function testGetMediaAlchemystSpec()
    {
        $this->assertInstanceOf(FlexPaperSpecification::class, $this->object->getMediaAlchemystSpec());
    }
}
