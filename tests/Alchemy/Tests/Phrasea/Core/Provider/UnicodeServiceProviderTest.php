<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Core\Provider\UnicodeServiceProvider
 */
class UnicodeServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\UnicodeServiceProvider', 'unicode', '\unicode'],
        ];
    }
}
