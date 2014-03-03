<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider
 */
class SearchEngineServiceProviderTest extends ServiceProviderTestCase
{
    public function setUp()
    {
        if (!extension_loaded('phrasea2')) {
            $this->markTestSkipped('Phrasea2 is required for this test');
        }
        parent::setUp();
    }

    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider', 'phraseanet.SE', 'Alchemy\Phrasea\SearchEngine\SearchEngineInterface'],
        ];
    }
}
