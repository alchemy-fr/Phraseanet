<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Tests\Repositories;

use Entities\User;

class UserRepositoryTest extends \PhraseanetPHPUnitAbstract
{
    public function testFindAdmins()
    {
        $this->markTestSkipped('missing deleted field');
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $this->insertOneUser($user);
        $users = self::$DI['app']['EM']->getRepository('Entities\User')->findAdmins();
        $this->assertEquals(0, count($users));

        $user->setAdmin(true);
        $this->insertOneUser($user);
        $users = self::$DI['app']['EM']->getRepository('Entities\User')->findAdmins();
        $this->assertEquals(1, count($users));

        $user->setModelOf(1);
        $this->insertOneUser($user);
        $users = self::$DI['app']['EM']->getRepository('Entities\User')->findAdmins();
        $this->assertEquals(0, count($users));

        $user->setModelOf(null);
        $user->setModelOf(true);
        $this->insertOneUser($user);
        $users = self::$DI['app']['EM']->getRepository('Entities\User')->findAdmins();
        $this->assertEquals(0, count($users));
    }

    public function testSetAdmins()
    {
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $this->insertOneUser($user);
        $this->assertFalse($user->isAdmin());
        self::$DI['app']['EM']->getRepository('Entities\User')->setAdmins(array($user));
        $user = self::$DI['app']['EM']->getReference('Entities\User', $user->getId());
        self::$DI['app']['EM']->refresh($user);
        $this->assertTrue($user->isAdmin());
    }

    public function testResetAdmins()
    {
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $user->setAdmin(true);
        $this->insertOneUser($user);
        $this->assertTrue($user->isAdmin());
        self::$DI['app']['EM']->getRepository('Entities\User')->resetAdmins();
        $user = self::$DI['app']['EM']->getReference('Entities\User', $user->getId());
        self::$DI['app']['EM']->refresh($user);
        $this->assertFalse($user->isAdmin());
    }

    public function testFindByLogin()
    {
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $this->insertOneUser($user);
        $user = self::$DI['app']['EM']->getRepository('Entities\User')->findByLogin('login');
        $this->assertInstanceOf('Entities\User', $user);
        $this->assertNull(self::$DI['app']['EM']->getRepository('Entities\User')->findByLogin('wrong-login'));
    }

    public function testFindByEmail()
    {
        $this->markTestSkipped('missing deleted field');
        $user = new User();
        $user->setLogin('login');
        $user->setPassword('toto');
        $user->setEmail('toto@toto.to');
        $this->insertOneUser($user);
        $userFound = self::$DI['app']['EM']->getRepository('Entities\User')->findByEmail('toto@toto.to');
        $this->assertInstanceOf('Entities\User', $userFound);

        $user->setDeleted(true);
        $this->insertOneUser($user);
        $userFound = self::$DI['app']['EM']->getRepository('Entities\User')->findByEmail('toto@toto.to');
        $this->assertNull($userFound);

        $user->setEmail(null);
        $this->insertOneUser($user);
        $userFound = self::$DI['app']['EM']->getRepository('Entities\User')->findByEmail(null);
        $this->assertNull($userFound);
    }
}