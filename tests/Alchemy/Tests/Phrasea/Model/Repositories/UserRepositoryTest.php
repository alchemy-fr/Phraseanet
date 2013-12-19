<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\User;

class UserRepositoryTest extends \PhraseanetTestCase
{
    public function testFindAdminsWithNoAdmins()
    {
        $users = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findAdmins();
        $this->assertEquals(1, count($users));
    }

    public function testFindByLogin()
    {
        $user = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findByLogin('user1');
        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $user);
        $this->assertNull(self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findByLogin('wrong-login'));
    }

    public function testFindUserByEmail()
    {
        $user = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findByEmail('user2@phraseanet.com');
        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $user);
    }
}
