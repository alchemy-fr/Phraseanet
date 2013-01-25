<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\GeonamesServiceProvider
 */
class GeonamesServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\GeonamesServiceProvider', 'geonames', 'geonames'),
        );
    }
}
