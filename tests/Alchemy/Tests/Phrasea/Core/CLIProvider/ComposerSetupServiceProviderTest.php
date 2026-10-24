<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

/**
 * @covers Alchemy\Phrasea\Core\CLIProvider\ComposerSetupServiceProvider
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ComposerSetupServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\CLIProvider\ComposerSetupServiceProvider',
                'composer-setup',
                'Alchemy\Phrasea\Utilities\ComposerSetup'
            ],
        ];
    }
}
