<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\PhpRequirements;

/**
 * @group functional
 * @group legacy
 */
class PhpRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new PhpRequirements;
    }
}
