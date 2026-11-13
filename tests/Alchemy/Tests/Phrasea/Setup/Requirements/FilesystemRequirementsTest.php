<?php

namespace Alchemy\Tests\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\Requirements\FilesystemRequirements;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class FilesystemRequirementsTest extends RequirementsTestCase
{
    protected function provideRequirements()
    {
        return new FilesystemRequirements(self::$DI['app']['conf']);
    }
}
