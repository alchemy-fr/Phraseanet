<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\SystemRequirements;

class SystemRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new SystemRequirements;
    }
}
