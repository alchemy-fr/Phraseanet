<?php

namespace Alchemy\Phrasea\Core\Provider;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class CacheServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new CacheServiceProvider());

        $this->assertInstanceof('Doctrine\\Common\\Cache\\Cache', self::$DI['app']['cache']);
        $this->assertInstanceof('Doctrine\\Common\\Cache\\Cache', self::$DI['app']['opcode-cache']);
        $this->assertInstanceof('Alchemy\\Phrasea\\Cache\\Manager', self::$DI['app']['phraseanet.cache-service']);
    }
}
