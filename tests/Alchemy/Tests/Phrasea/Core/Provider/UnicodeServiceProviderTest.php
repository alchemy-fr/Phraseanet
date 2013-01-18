<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\UnicodeServiceProvider;

class UnicodeServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @covers Alchemy\Phrasea\Core\Provider\UnicodeServiceProvider
     */
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new UnicodeServiceProvider());

        $this->assertInstanceof('unicode', self::$DI['app']['unicode']);
    }
}
