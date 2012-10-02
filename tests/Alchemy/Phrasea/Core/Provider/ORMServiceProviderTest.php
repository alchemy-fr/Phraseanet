<?php

namespace Alchemy\Phrasea\Core\Provider;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class ORMServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new ORMServiceProvider());

        $this->assertInstanceof('Doctrine\\ORM\\EntityManager', self::$DI['app']['EM']);
    }
}
