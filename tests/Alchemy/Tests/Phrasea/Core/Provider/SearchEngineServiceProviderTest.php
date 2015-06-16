<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider
 */
class SearchEngineServiceProviderTest extends ServiceProviderTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider', 'phraseanet.SE', 'Alchemy\Phrasea\SearchEngine\SearchEngineInterface'],
        ];
    }
}
