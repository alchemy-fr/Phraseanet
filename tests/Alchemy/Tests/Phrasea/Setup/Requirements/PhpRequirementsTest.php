<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\PhpRequirements;

class PhpRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new PhpRequirements;
    }
}
