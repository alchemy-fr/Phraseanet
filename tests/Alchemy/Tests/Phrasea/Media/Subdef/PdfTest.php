<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Pdf;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Tests\Tools\TranslatorMockTrait;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PdfTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /**
     * @var Pdf
     */
    protected $object;

    public function setUp(): void
    {
        parent::setUp();
        $this->object = new Pdf($this->createTranslatorMock());
    }

    public function testGetType()
    {
        $this->assertEquals(Subdef::TYPE_PDF, $this->object->getType());
    }

    public function testGetDescription()
    {
        $this->assertTrue(is_string($this->object->getDescription()));
    }

    public function testGetMediaAlchemystSpec()
    {
        $this->assertInstanceOf('Alchemy\\Phrasea\\Media\\Subdef\\Specification\\PdfSpecification', $this->object->getMediaAlchemystSpec());
    }
}
