<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider
 */
class ConfigurationTesterServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider', 'phraseanet.configuration-tester', 'Alchemy\\Phrasea\\Setup\\ConfigurationTester'),
        );
    }
}
