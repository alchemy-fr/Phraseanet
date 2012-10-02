<?php

namespace Alchemy\Phrasea\Core\Provider;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class ConfigurationTesterServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new ConfigurationTesterServiceProvider());

        $this->assertInstanceof('Alchemy\\Phrasea\\Setup\\ConfigurationTester', self::$DI['app']['phraseanet.configuration-tester']);
    }
}
