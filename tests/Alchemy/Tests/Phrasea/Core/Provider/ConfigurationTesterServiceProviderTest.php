<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ConfigurationTesterServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider', 'phraseanet.configuration-tester', 'Alchemy\\Phrasea\\Setup\\ConfigurationTester'],
        ];
    }
}
