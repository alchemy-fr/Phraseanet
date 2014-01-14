<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

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
