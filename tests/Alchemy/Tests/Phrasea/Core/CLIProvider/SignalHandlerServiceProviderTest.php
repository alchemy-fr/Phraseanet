<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

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
