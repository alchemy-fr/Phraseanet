<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\FeedServiceProvider
 */
class FeedServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\FeedServiceProvider',
                'feed.user-link-generator',
                'Alchemy\Phrasea\Feed\LinkGenerator'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\FeedServiceProvider',
                'feed.aggregate-link-generator',
                'Alchemy\Phrasea\Feed\AggregateLinkGenerator'
            ),
        );
    }
}
