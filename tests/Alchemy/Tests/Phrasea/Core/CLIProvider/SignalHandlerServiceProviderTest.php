<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

class SignalHandlerServiceProvidertest extends ServiceProviderTestCase
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
