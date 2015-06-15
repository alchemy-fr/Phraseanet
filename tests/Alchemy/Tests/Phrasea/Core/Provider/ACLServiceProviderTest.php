<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

/**
 * @group functional
 * @group legacy
 */
class ACLServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\Provider\ACLServiceProvider',
                'acl.basket',
                'Alchemy\\Phrasea\\ACL\\BasketACL',
            ],
        ];
    }
}
