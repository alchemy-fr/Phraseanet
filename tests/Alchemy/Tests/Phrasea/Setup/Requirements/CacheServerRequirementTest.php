<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\CacheServerRequirement;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class CacheServerRequirementTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new CacheServerRequirement;
    }
}
