<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\FilesystemRequirements;

/**
 * @group functional
 * @group legacy
 */
class FilesystemRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new FilesystemRequirements($this->app['conf']);
    }
}
