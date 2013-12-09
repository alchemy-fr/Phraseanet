<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class CacheConnectionServiceProvidertest extends ServiceProviderTestCase
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
