<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\BorderManagerServiceProvider;

class BorderManagerServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new BorderManagerServiceProvider());

        $borderManager1 = self::$DI['app']['border-manager'];
        $borderManager2 = self::$DI['app']['border-manager'];

        $this->assertInstanceof('Alchemy\\Phrasea\\Border\\Manager', $borderManager1);

        $this->assertEquals($borderManager1, $borderManager2);
    }
}
