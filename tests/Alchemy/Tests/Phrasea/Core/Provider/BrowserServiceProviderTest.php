<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\BrowserServiceProvider;

class BrowserServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new BrowserServiceProvider());

        $this->assertInstanceof('Browser', self::$DI['app']['browser']);
    }
}
