<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 * @covers Alchemy\Phrasea\Core\Provider\BrowserServiceProvider
 */
class BrowserServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\BrowserServiceProvider', 'browser', 'Browser'],
        ];
    }
}
