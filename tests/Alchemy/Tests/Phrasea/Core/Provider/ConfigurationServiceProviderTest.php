<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;

class ConfigurationServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new ConfigurationServiceProvider());

        $this->assertInstanceof('Alchemy\\Phrasea\\Core\\Configuration', self::$DI['app']['phraseanet.configuration']);
    }
}
