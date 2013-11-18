<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\FeedServiceProvider
 */
class FeedServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\FeedServiceProvider',
                'feed.user-link-generator',
                'Alchemy\Phrasea\Feed\Link\FeedLinkGenerator'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\FeedServiceProvider',
                'feed.aggregate-link-generator',
                'Alchemy\Phrasea\Feed\Link\AggregateLinkGenerator'
            ],
        ];
    }
}
