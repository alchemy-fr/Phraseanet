<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider
 */
class ConfigurationTesterServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider', 'phraseanet.configuration-tester', 'Alchemy\\Phrasea\\Setup\\ConfigurationTester'],
        ];
    }
}
