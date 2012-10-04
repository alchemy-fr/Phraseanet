<?php

namespace Alchemy\Phrasea\Core\Provider;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class BorderManagerServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new BorderManagerServiceProvider());

        $this->assertInstanceof('Alchemy\\Phrasea\\Border\\Manager', self::$DI['app']['border-manager']);
    }
}
