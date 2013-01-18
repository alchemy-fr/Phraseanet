<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\GeonamesServiceProvider;

class GeonamesServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new GeonamesServiceProvider());

        $this->assertInstanceof('geonames', self::$DI['app']['geonames']);
    }
}
