<?php

namespace Alchemy\Tests\Phrasea\Authentication;

class ACLProviderTest extends \PhraseanetPHPUnitAbstract
{
    public function testGetACL()
    {
        $acl = self::$DI['app']['acl']->get(self::$DI['user']);

        $this->assertInstanceOf('\ACL', $acl);
    }
}
