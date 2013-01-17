<?php

namespace Alchemy\Phrasea\Core\Provider;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

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
