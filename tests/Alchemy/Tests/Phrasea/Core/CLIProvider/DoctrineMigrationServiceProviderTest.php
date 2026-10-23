<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class DoctrineMigrationServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
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
