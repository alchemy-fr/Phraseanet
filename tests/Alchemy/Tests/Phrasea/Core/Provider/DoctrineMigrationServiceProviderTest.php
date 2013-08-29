<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\UnicodeServiceProvider
 */
class DoctrineMigrationServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\DoctrineMigrationServiceProvider', 'doctrine.migration', 'Doctrine\DBAL\Migrations\Migration'),
        );
    }
}
