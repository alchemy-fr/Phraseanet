<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

/**
 * @group functional
 * @group legacy
 */
class DoctrineMigrationServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\CLIProvider\DoctrineMigrationServiceProvider',
                'doctrine-migration.configuration',
                'Doctrine\DBAL\Migrations\Configuration\YamlConfiguration'
            ]
        ];
    }
}
