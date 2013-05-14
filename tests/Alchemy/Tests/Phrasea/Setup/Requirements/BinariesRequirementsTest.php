<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\BinariesRequirements;

class BinariesRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new BinariesRequirements;
    }
}
