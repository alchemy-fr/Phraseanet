<?php

namespace Alchemy\Tests\Phrasea\Authentication;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class ACLProviderTest extends \PhraseanetTestCase
{
    public function testGetACL()
    {
        $acl = self::$DI['app']->getAclForUser(self::$DI['user']);

        $this->assertInstanceOf('\ACL', $acl);
    }
}
