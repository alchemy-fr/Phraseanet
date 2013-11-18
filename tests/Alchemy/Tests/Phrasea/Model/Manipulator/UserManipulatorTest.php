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

use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Alchemy\Phrasea\Model\Entities\User;

class UserManipulatorTest extends \PhraseanetPHPUnitAbstract
{
    public function testCreateUser()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'pass');
        $this->assertInstanceOf('\Alchemy\Phrasea\Model\Entities\User', self::$DI['app']['manipulator.user']->getRepository()->findOneByLogin('login'));
    }

    public function testCreateAdminUser()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'pass', 'admin@admin.com', true);
        $user = self::$DI['app']['manipulator.user']->getRepository()->findOneByLogin('login');
        $this->assertTrue($user->isAdmin());
        $this->assertNotNull($user->getEmail());
    }

    public function testCreateTemplate()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'pass');
        $template = self::$DI['app']['manipulator.user']->createTemplate('test', $user);
        $user = self::$DI['app']['manipulator.user']->getRepository()->findOneByLogin('test');
        $this->assertTrue($user->isTemplate());
    }

    public function testSetPassword()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'password');
        $curPassword = $user->getPassword();
        self::$DI['app']['manipulator.user']->setPassword($user, 'toto');
        $this->assertNotEquals($curPassword, $user->getPassword());
    }

    public function testSetGeonameId()
    {
        $manager = $this->getMockBuilder('Alchemy\Phrasea\Model\Manager\UserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $geoname = $this->getMockBuilder('Alchemy\Geonames\Geoname')
            ->disableOriginalConstructor()
            ->getMock();

        $geoname->expects($this->once())
            ->method('get')
            ->with($this->equalTo('country'))
            ->will($this->returnValue(['code' => 'fr']));

        $geonamesConnector = $this->getMockBuilder('Alchemy\Geonames\Connector')
            ->disableOriginalConstructor()
            ->getMock();

        $geonamesConnector->expects($this->once())
            ->method('geoname')
            ->with($this->equalTo(4))
            ->will($this->returnValue($geoname));

        $passwordInterface = $this->getMockBuilder('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface')
            ->getMock();

        $user = self::$DI['app']['manipulator.user']->createUser('login', 'password');
        $manipulator = new UserManipulator($manager, $passwordInterface, $geonamesConnector);

        $manipulator->setGeonameId($user, 4);
        $this->assertEquals(4, $user->getGeonameId());
    }

    public function testPromote()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'toto');
        $this->assertFalse($user->isAdmin());
        $user2 = self::$DI['app']['manipulator.user']->createUser('login2', 'toto');
        $this->assertFalse($user2->isAdmin());
        self::$DI['app']['manipulator.user']->promote([$user, $user2]);
        $user = self::$DI['app']['manipulator.user']->getRepository()->findOneByLogin('login');
        $this->assertTrue($user->isAdmin());
        $user2 = self::$DI['app']['manipulator.user']->getRepository()->findOneByLogin('login');
        $this->assertTrue($user2->isAdmin());
    }

    public function testDemote()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'toto', null, true);
        $this->assertTrue($user->isAdmin());
        self::$DI['app']['manipulator.user']->demote($user);
        $user = self::$DI['app']['manipulator.user']->getRepository()->findOneByLogin('login');
        $this->assertFalse($user->isAdmin());
    }

    public function testSetEmail()
    {
        self::$DI['app']['manipulator.user']->createUser('login', 'password', 'test@test.fr');
        $user = self::$DI['app']['manipulator.user']->createUser('login2', 'password', 'test2@test.fr');
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\RuntimeException',
            'User with email test@test.fr already exists.'
        );
        self::$DI['app']['manipulator.user']->setEmail($user, 'test@test.fr');
    }

    public function testInvalidGeonamedId()
    {
        $manager = $this->getMockBuilder('Alchemy\Phrasea\Model\Manager\UserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $geoname = $this->getMockBuilder('Alchemy\Geonames\Geoname')
            ->disableOriginalConstructor()
            ->getMock();

        $geoname->expects($this->once())
            ->method('get')
            ->with($this->equalTo('country'))
            ->will($this->returnValue(['code' => 'fr']));

        $geonamesConnector = $this->getMockBuilder('Alchemy\Geonames\Connector')
            ->disableOriginalConstructor()
            ->getMock();

        $geonamesConnector->expects($this->once())
            ->method('geoname')
            ->with($this->equalTo(-1))
            ->will($this->returnValue($geoname));

        $passwordInterface = $this->getMockBuilder('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface')
            ->getMock();
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'password');
        $manipulator = new UserManipulator($manager, $passwordInterface, $geonamesConnector);
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\InvalidArgumentException',
            'Invalid geonameid -1.'
        );
        $manipulator->setGeonameId($user, -1);
    }

    public function testInvalidEmail()
    {
        self::$DI['app']['manipulator.user']->createUser('login', 'password', 'test@test.fr');
        $user = self::$DI['app']['manipulator.user']->createUser('login2', 'password', 'test2@test.fr');
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\InvalidArgumentException',
            'Email testtest.fr is not legal.'
        );
        self::$DI['app']['manipulator.user']->setEmail($user, 'testtest.fr');
    }

    public function testInvalidSetModelOf()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'password');
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\RuntimeException',
            'User with login login already exists.'
        );
        self::$DI['app']['manipulator.user']->createTemplate('login', $user);
    }

    public function testAddUserSetting()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'password');
        self::$DI['app']['manipulator.user']->addUserSetting($user, 'name' ,'value');
        $user = self::$DI['app']['manipulator.user']->getRepository()->findOneByLogin('login');
        $this->assertCount(1, $user->getSettings());
    }

    public function testAddNotificationSetting()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'password');
        self::$DI['app']['manipulator.user']->addNotificationSetting($user, 'name', 'value');
        $user = self::$DI['app']['manipulator.user']->getRepository()->findOneByLogin('login');
        $this->assertCount(1, $user->getNotificationSettings());
    }

    public function testAddQuery()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'password');
        self::$DI['app']['manipulator.user']->logQuery($user, 'query');
        $user = self::$DI['app']['manipulator.user']->getRepository()->findOneByLogin('login');
        $this->assertCount(1, $user->getQueries());
    }
}
