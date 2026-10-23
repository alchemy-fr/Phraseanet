<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\LocalesRequirements;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class LocalesRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new LocalesRequirements;
    }
}
