<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider
 */
class SearchEngineServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider', 'phraseanet.SE', 'Alchemy\Phrasea\SearchEngine\SearchEngineInterface'),
        );
    }
}
