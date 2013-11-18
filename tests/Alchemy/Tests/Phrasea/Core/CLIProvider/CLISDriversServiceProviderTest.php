<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

use Alchemy\Phrasea\CLI;

/**
 * @covers Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider
 */
class CLISDriversServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider',
                'executable-finder',
                'Symfony\Component\Process\ExecutableFinder'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider',
                'driver.bower',
                'Alchemy\Phrasea\Command\Developer\Utils\BowerDriver'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider',
                'driver.composer',
                'Alchemy\Phrasea\Command\Developer\Utils\ComposerDriver'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider',
                'driver.uglifyjs',
                'Alchemy\Phrasea\Command\Developer\Utils\UglifyJsDriver'
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\CLIDriversServiceProvider',
                'driver.recess',
                'Alchemy\Phrasea\Command\Developer\Utils\RecessDriver'
            ],
        ];
    }

    public function testComposerTimeout()
    {
        $cli = new CLI('test');
        $this->assertEquals(300, $cli['driver.composer']->getProcessBuilderFactory()->getTimeout());
    }

    public function testBowerTimeout()
    {
        $cli = new CLI('test');
        $this->assertEquals(300, $cli['driver.bower']->getProcessBuilderFactory()->getTimeout());
    }
}
