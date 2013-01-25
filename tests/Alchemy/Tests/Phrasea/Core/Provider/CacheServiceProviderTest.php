<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\CacheServiceProvider
 */
class CacheServiceProvidertest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array('Alchemy\Phrasea\Core\Provider\CacheServiceProvider', 'cache', 'Doctrine\\Common\\Cache\\Cache'),
            array('Alchemy\Phrasea\Core\Provider\CacheServiceProvider', 'opcode-cache', 'Doctrine\\Common\\Cache\\Cache'),
            array('Alchemy\Phrasea\Core\Provider\CacheServiceProvider', 'phraseanet.cache-service', 'Alchemy\\Phrasea\\Cache\\Manager'),
        );
    }
}
