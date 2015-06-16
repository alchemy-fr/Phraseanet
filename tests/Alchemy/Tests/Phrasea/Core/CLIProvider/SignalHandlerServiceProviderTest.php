<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

/**
 * @group functional
 * @group legacy
 */
class SignalHandlerServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\CLIProvider\SignalHandlerServiceProvider',
                'signal-handler',
                'Neutron\SignalHandler\SignalHandler'
            ],
        ];
    }
}
