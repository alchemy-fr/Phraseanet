<?php

namespace Alchemy\Phrasea\Media\Subdef\Specification;

use MediaAlchemyst\Specification\AbstractSpecification;

/**
 * Specification of the legacy "flexpaper" (document to SWF) subdefinitions.
 *
 * Media-Alchemyst has no SWF transmuter anymore: such subdefinitions are still read from the databox structure
 * (and existing files are still served), but they are not generated.
 */
class FlexPaperSpecification extends AbstractSpecification
{
    const TYPE_FLEXPAPER = 'flexpaper';

    public function getType()
    {
        return self::TYPE_FLEXPAPER;
    }
}
