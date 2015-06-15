<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\BinariesRequirements;

/**
 * @group functional
 * @group legacy
 */
class BinariesRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new BinariesRequirements;
    }
}
