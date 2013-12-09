<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef;

use Alchemy\Phrasea\Media\Subdef\FlexPaper;
use Alchemy\Phrasea\Media\Subdef\Subdef;
use Alchemy\Tests\Tools\TranslatorMockTrait;

class FlexPaperTest extends \PHPUnit_Framework_TestCase
{
    use TranslatorMockTrait;

    /**
     * @var FlexPaper
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new FlexPaper($this->createTranslatorMock());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\FlexPaper::getType
     */
    public function testGetType()
    {
        $this->assertEquals(Subdef::TYPE_FLEXPAPER, $this->object->getType());
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\FlexPaper::getDescription
     */
    public function testGetDescription()
    {
        $this->assertTrue(is_string($this->object->getDescription()));
    }

    /**
     * @covers Alchemy\Phrasea\Media\Subdef\FlexPaper::getMediaAlchemystSpec
     */
    public function testGetMediaAlchemystSpec()
    {
        $this->assertInstanceOf('\\MediaAlchemyst\\Specification\\Flash', $this->object->getMediaAlchemystSpec());
    }
}
