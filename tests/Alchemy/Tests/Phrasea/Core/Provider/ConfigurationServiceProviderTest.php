<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider
 */
class ConfigurationServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider', 'phraseanet.configuration', 'Alchemy\\Phrasea\\Core\\Configuration'),
        );
    }
}
