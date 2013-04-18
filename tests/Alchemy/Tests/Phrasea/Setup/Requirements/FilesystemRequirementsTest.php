<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\FilesystemRequirements;

class FilesystemRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new FilesystemRequirements;
    }
}
