<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

/**
 * @covers Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider
 */
class CLISDriversServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider',
                'executable-finder',
                'Symfony\Component\Process\ExecutableFinder'
            ),
            array(
                'Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider',
                'driver.bower',
                'Alchemy\Phrasea\Command\Developer\Utils\BowerDriver'
            ),
            array(
                'Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider',
                'driver.composer',
                'Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver'
            ),
            array(
                'Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider',
                'driver.uglifyjs',
                'Alchemy\Phrasea\Command\Developer\Utils\UglifyJsDriver'
            ),
        );
    }
}
