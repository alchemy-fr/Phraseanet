<?php

namespace Alchemy\Tests\Phrasea\Core\Provider;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ACLServiceProviderTest extends ServiceProviderTestCase
{
    public static function provideServiceDescription()
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
