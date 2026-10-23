<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef\Specification;

use Alchemy\Phrasea\Media\Subdef\Specification\PdfSpecification;

class PdfTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @covers Alchemy\Phrasea\Media\Subdef\Specification\PdfSpecification::getType
     */
    public function testGetType()
    {
        $specs = new PdfSpecification();
        $this->assertEquals(PdfSpecification::TYPE_PDF, $specs->getType());
    }
}