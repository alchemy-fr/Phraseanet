<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\ORMServiceProvider
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ORMServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            //['Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM', 'Doctrine\\ORM\\EntityManager'],
            //['Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'orm.sql-logger', 'Alchemy\\Phrasea\\Model\\MonologSQLLogger'],
            //['Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM.driver', 'Doctrine\\Common\\Persistence\\Mapping\\Driver\\MappingDriverChain'],
            //['Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM.config', 'Doctrine\\ORM\\Configuration'],
            ['Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'db.event_manager', 'Doctrine\\Common\\EventManager'],
        ];
    }
}
