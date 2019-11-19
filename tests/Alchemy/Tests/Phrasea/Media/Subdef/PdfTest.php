<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\Pdf;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Tests\Tools\TranslatorMockTrait;

/**
 * @group functional
 * @group legacy
 */
class PdfTest extends \PhraseanetTestCase
{
    use TranslatorMockTrait;

    /**
     * @var Pdf
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new Pdf($this->createTranslatorMock());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Pdf::getType
     */
    public function testGetType()
    {
        $this->assertEquals(Subdef::TYPE_PDF, $this->object->getType());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Pdf::getDescription
     */
    public function testGetDescription()
    {
        $this->assertTrue(is_string($this->object->getDescription()));
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Pdf::getMediaAlchemystSpec
     */
    public function testGetMediaAlchemystSpec()
    {
        $this->assertInstanceOf('Alchemy\\Phrasea\\Media\\Subdef\\Specification\\PdfSpecification', $this->object->getMediaAlchemystSpec());
    }
}
