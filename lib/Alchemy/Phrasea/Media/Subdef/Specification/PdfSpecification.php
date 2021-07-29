<?php

namespace Alchemy\Phrasea\Media\Subdef\Specification;

use MediaAlchemyst\Specification\AbstractSpecification;

class PdfSpecification extends AbstractSpecification
{
    const TYPE_PDF = 'pdf';

    public function getType()
    {
        return self::TYPE_PDF;
    }
}