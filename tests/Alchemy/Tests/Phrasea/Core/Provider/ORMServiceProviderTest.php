<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\ORMServiceProvider
 */
class ORMServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM', 'Doctrine\\ORM\\EntityManager'),
            array('Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM.sql-logger', 'Alchemy\\Phrasea\\Model\\MonologSQLLogger'),
            array('Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM.driver', 'Doctrine\\ORM\Mapping\\Driver\\DriverChain'),
            array('Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM.config', 'Doctrine\\ORM\\Configuration'),
            array('Alchemy\Phrasea\Core\Provider\ORMServiceProvider', 'EM.events-manager', 'Doctrine\\Common\\EventManager'),
        );
    }
}
