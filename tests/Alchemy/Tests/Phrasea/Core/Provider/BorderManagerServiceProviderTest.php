<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\BorderManagerServiceProvider;

class BorderManagerServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new BorderManagerServiceProvider());

        $this->assertInstanceof('Alchemy\\Phrasea\\Border\\Manager', self::$DI['app']['border-manager']);
    }
}
