<?php

namespace Alchemy\Tests\Phrasea\Media\Subdef\Specification;

use Alchemy\Phrasea\Media\Subdef\Specification\PdfSpecification;

class PdfSpecificationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetType()
    {
        $specs = new PdfSpecification();
        $this->assertEquals(PdfSpecification::TYPE_PDF, $specs->getType());
    }
}