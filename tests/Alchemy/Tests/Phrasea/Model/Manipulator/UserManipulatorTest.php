<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Entities\User;

class UserManipulatorTest extends \PhraseanetPHPUnitAbstract
{
    public function testCreateUser()
    {
        $user = self::$DI['app']['user.manipulator']->createUser('login', 'pass');
        $this->assertInstanceOf('\Entities\User', self::$DI['app']['user.manipulator']->getRepository()->findOneByLogin('login'));
    }
    
    public function testCreateAdminUser()
    {
        $user = self::$DI['app']['user.manipulator']->createUser('login', 'pass', 'admin@admin.com', true);
        $user = self::$DI['app']['user.manipulator']->getRepository()->findOneByLogin('login');
        $this->assertTrue($user->isAdmin());
        $this->assertNotNull($user->getEmail());
    }
    
    public function testCreateGuest()
    {
        $user = self::$DI['app']['user.manipulator']->createGuest();
        $user = self::$DI['app']['user.manipulator']->getRepository()->findOneByLogin(User::USER_GUEST);
        $this->assertTrue($user->isSpecial());
    }
    
    public function testCreateAutoRegister()
    {
        $user = self::$DI['app']['user.manipulator']->createAutoRegister();
        $user = self::$DI['app']['user.manipulator']->getRepository()->findOneByLogin(User::USER_AUTOREGISTER);
        $this->assertTrue($user->isSpecial());
    }
    
    public function testCreateTemplate()
    {
        $template = self::$DI['app']['user.manipulator']->createUser('login', 'pass');
        $user = self::$DI['app']['user.manipulator']->createTemplate('test', $template);
        $user = self::$DI['app']['user.manipulator']->getRepository()->findOneByLogin('test');
        $this->assertTrue($user->isTemplate());
    }
    
    public function testSetPassword()
    {
        $user = self::$DI['app']['user.manipulator']->createUser('login', 'password');
        $curPassword = $user->getPassword();
        self::$DI['app']['user.manipulator']->setPassword($user, 'toto');
        $this->assertNotEquals($curPassword, $user->getPassword());
    }

    public function testSetGeonameId()
    {
        $manager = $this->getMockBuilder('Alchemy\Phrasea\Model\Manager\UserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->once())
                ->method('onUpdateGeonameId');

        $user = self::$DI['app']['user.manipulator']->createUser('login', 'password');
        $manipulator = new UserManipulator($manager, self::$DI['app']['EM']);

        $manipulator->setGeonameId($user, 4);
        $this->assertEquals(4, $user->getGeonameId());
    }

    public function testPromote()
    {
        $user = self::$DI['app']['user.manipulator']->createUser('login', 'toto');
        $this->assertFalse($user->isAdmin());
        $user2 = self::$DI['app']['user.manipulator']->createUser('login2', 'toto');
        $this->assertFalse($user2->isAdmin());
        self::$DI['app']['user.manipulator']->promote(array($user, $user2));
        $user = self::$DI['app']['user.manipulator']->getRepository()->findOneByLogin('login');
        $this->assertTrue($user->isAdmin());
        $user2 = self::$DI['app']['user.manipulator']->getRepository()->findOneByLogin('login');
        $this->assertTrue($user2->isAdmin());
    }

    public function testDemote()
    {
        $user = self::$DI['app']['user.manipulator']->createUser('login', 'toto', null, true);
        $this->assertTrue($user->isAdmin());
        self::$DI['app']['user.manipulator']->demote($user);
        $user = self::$DI['app']['user.manipulator']->getRepository()->findOneByLogin('login');
        $this->assertFalse($user->isAdmin());
    }

    public function testSetLogin()
    {
        self::$DI['app']['user.manipulator']->createUser('login', 'password');
        $user2 = self::$DI['app']['user.manipulator']->createUser('login2', 'password');

        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\RuntimeException',
            'User with login login already exists.'
        );
        self::$DI['app']['user.manipulator']->setLogin($user2, 'login');
    }

    public function testSetEmail()
    {
        self::$DI['app']['user.manipulator']->createUser('login', 'password', 'test@test.fr');
        $user2 = self::$DI['app']['user.manipulator']->createUser('login2', 'password', 'test2@test.fr');

        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\RuntimeException',
            'User with email test@test.fr already exists.'
        );
        self::$DI['app']['user.manipulator']->setEmail($user2, 'test@test.fr');
    }
}
