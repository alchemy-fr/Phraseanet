<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\BorderManagerServiceProvider
 */
class BorderManagerServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\BorderManagerServiceProvider', 'border-manager', 'Alchemy\\Phrasea\\Border\\Manager'),
        );
    }
}
