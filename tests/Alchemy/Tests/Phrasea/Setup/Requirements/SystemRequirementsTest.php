<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\SystemRequirements;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SystemRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new SystemRequirements;
    }
}
