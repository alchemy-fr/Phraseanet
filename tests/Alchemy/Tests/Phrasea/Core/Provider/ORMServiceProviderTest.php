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
        );
    }
}
