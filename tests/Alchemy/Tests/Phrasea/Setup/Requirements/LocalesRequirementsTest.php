<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\LocalesRequirements;

/**
 * @group functional
 * @group legacy
 */
class LocalesRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new LocalesRequirements;
    }
}
