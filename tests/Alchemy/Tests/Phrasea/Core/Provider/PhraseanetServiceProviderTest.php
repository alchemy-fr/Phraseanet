<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\FeedServiceProvider
 */
class PhraseanetServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'phraseanet.appbox',
                '\appbox'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'firewall',
                'Alchemy\Phrasea\Security\Firewall'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'events-manager',
                '\eventsmanager_broker'
            ],
            [
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'acl',
                'Alchemy\Phrasea\Authentication\ACLProvider'
            ]
        ];
    }
}
