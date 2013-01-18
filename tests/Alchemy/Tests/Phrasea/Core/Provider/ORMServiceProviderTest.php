<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\ORMServiceProvider;

class ORMServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new ORMServiceProvider());

        $this->assertInstanceof('Doctrine\\ORM\\EntityManager', self::$DI['app']['EM']);
    }
}
