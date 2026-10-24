<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\BrowserServiceProvider
 */
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class BrowserServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\BrowserServiceProvider', 'browser', 'Browser'],
        ];
    }
}
