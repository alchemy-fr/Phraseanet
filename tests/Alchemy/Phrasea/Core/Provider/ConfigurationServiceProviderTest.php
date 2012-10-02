<?php

namespace Alchemy\Phrasea\Core\Provider;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class ConfigurationServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new ConfigurationServiceProvider());

        $this->assertInstanceof('Alchemy\\Phrasea\\Core\\Configuration', self::$DI['app']['phraseanet.configuration']);
    }
}
