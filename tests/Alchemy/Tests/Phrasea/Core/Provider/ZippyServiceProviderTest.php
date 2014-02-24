<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

class ZippyServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            ['Alchemy\Phrasea\Core\Provider\ZippyServiceProvider', 'zippy', 'Alchemy\Zippy\Zippy'],
        ];
    }
}
