<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 */
class CacheConnectionServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\CacheConnectionServiceProvider',
                'cache.connection-factory',
                'Alchemy\\Phrasea\\Cache\\ConnectionFactory'
            ],
        ];
    }
}
