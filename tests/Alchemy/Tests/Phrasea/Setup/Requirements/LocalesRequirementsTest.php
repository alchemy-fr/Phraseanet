<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\LocalesRequirements;

class LocalesRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new LocalesRequirements;
    }
}
