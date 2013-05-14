<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\OpcodeCacheRequirement;

class OpcodeCacheRequirementTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new OpcodeCacheRequirement;
    }
}
