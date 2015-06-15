<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Core\Provider\TokensServiceProvider
 */
class TokensServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\TokensServiceProvider', 'tokens', '\random'],
        ];
    }
}
