<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider;

class SearchEngineServiceProvidertest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @covers Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider
     */
    public function testGetInstantiate()
    {
        self::$DI['app']->register(new SearchEngineServiceProvider());

        $this->assertInstanceof('Alchemy\Phrasea\SearchEngine\SearchEngineInterface', self::$DI['app']['phraseanet.SE']);
    }
}
