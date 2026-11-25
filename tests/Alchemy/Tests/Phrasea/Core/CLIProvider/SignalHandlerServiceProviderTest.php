<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class SignalHandlerServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
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
