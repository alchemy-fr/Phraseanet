<?php

namespace Alchemy\Tests\Phrasea\Authentication;

/**
 * @group functional
 * @group legacy
 */
class ACLProviderTest extends \PhraseanetTestCase
{
    public function testGetACL()
    {
        $acl = self::$DI['app']['acl']->get(self::$DI['user']);

        $this->assertInstanceOf('\ACL', $acl);
    }
}
