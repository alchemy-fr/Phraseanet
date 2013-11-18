<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\ORMServiceProvider
 */
class ORMServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM', 'Doctrine\\ORM\\EntityManager'],
            ['Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM.sql-logger', 'Alchemy\\Phrasea\\Model\\MonologSQLLogger'],
            ['Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM.driver', 'Doctrine\\ORM\Mapping\\Driver\\DriverChain'],
            ['Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM.config', 'Doctrine\\ORM\\Configuration'],
            ['Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM.events-manager', 'Doctrine\\Common\\EventManager'],
        ];
    }
}
