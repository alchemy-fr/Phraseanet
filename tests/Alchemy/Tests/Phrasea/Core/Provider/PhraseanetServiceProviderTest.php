<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @covers Alchemy\Phrasea\Core\Provider\FeedServiceProvider
 */
class PhraseanetServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return array(
            array(
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'phraseanet.appbox',
                '\appbox'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'phraseanet.registry',
                '\registry'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'firewall',
                'Alchemy\Phrasea\Security\Firewall'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'events-manager',
                '\eventsmanager_broker'
            ),
            array(
                'Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider',
                'acl',
                'Alchemy\Phrasea\Authentication\ACLProvider'
            )
        );
    }
}
