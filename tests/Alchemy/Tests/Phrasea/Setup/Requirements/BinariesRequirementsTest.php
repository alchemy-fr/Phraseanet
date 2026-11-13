<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\BinariesRequirements;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class BinariesRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new BinariesRequirements;
    }
}
