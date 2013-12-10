<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\User;

class UserRepositoryTest extends \PhraseanetTestCase
{
    public function testFindAdminsWithNoAdmins()
    {
        $this->insertOneUser('login');
        $users = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findAdmins();
        $this->assertEquals(0, count($users));
    }

    public function testFindAdminsWithOneAdmin()
    {
        $this->insertOneUser('login', null, true);
        $users = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findAdmins();
        $this->assertEquals(1, count($users));
    }

    public function testFindAdminsWithOneAdminButTemplate()
    {
        $user = $this->insertOneUser('login');
        $template = $this->insertOneUser('login2', null, true);

        $template->setModelOf($user);

        self::$DI['app']['EM']->persist($template);
        self::$DI['app']['EM']->flush();

        $users = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findAdmins();
        $this->assertEquals(0, count($users));
    }

    public function testFindAdminsWithOneAdminButDeleted()
    {
        $user = $this->insertOneUser('login', null, true);
        $user->setDeleted(true);

        self::$DI['app']['EM']->persist($user);
        self::$DI['app']['EM']->flush();

        $users = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findAdmins();
        $this->assertEquals(0, count($users));
    }

    public function testFindByLogin()
    {
        $this->insertOneUser('login');
        $user = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findByLogin('login');
        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $user);
        $this->assertNull(self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findByLogin('wrong-login'));
    }

    public function testFindUserByEmail()
    {
        $this->insertOneUser('login', 'toto@toto.to');
        $user = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findByEmail('toto@toto.to');
        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', $user);
    }

    public function testFindUserByEmailButDeleted()
    {
        $user = $this->insertOneUser('login', 'toto@toto.to');
        $user->setDeleted(true);

        self::$DI['app']['EM']->persist($user);
        self::$DI['app']['EM']->flush();

        $this->assertNull(self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findByEmail('toto@toto.to'));
    }

    public function testFindUserByEmailButNullEmail()
    {
        $user = $this->insertOneUser('login');
        $user->setDeleted(true);

        self::$DI['app']['EM']->persist($user);
        self::$DI['app']['EM']->flush();

        $this->assertNull(self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\User')->findByEmail('toto@toto.to'));
    }
}
