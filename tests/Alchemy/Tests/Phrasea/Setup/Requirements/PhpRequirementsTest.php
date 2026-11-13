<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\PhpRequirements;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PhpRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new PhpRequirements;
    }
}
