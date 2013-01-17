<?php

namespace Alchemy\Phrasea\Core\Provider;

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

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
