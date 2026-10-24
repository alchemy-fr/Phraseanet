<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class CacheConnectionServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
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
