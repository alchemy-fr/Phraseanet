<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\SystemRequirements;

/**
 * @group functional
 * @group legacy
 */
class SystemRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new SystemRequirements;
    }
}
