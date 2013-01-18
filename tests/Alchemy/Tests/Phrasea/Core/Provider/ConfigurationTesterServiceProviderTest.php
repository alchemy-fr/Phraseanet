<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider;

class ConfigurationTesterServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new ConfigurationTesterServiceProvider());

        $this->assertInstanceof('Alchemy\\Phrasea\\Setup\\ConfigurationTester', self::$DI['app']['phraseanet.configuration-tester']);
    }
}
