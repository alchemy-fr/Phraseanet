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
use Alchemy\Phrasea\Model\Manager\UserManager;
use Entities\UserQuery;

class UserManagerTest extends \PhraseanetPHPUnitAbstract
{
    public function testNewUser()
    {
        $user = self::$DI['app']['model.user-manager']->create();
        $this->assertInstanceOf('\Entities\User', $user);
    }

    public function testDeleteUser()
    {
        $user = self::$DI['app']['model.user-manipulator']->createUser('login', 'password');
        self::$DI['app']['model.user-manager']->update($user);
        self::$DI['app']['model.user-manager']->delete($user);
        $this->assertTrue($user->isDeleted());
        $this->assertNull($user->getEmail());
        $this->assertEquals('(#deleted_', substr($user->getLogin(), 0, 10));
    }
    
    public function testInvalidDeleteUser()
    {
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\InvalidArgumentException',
            'Entity of type `Entities\UserQuery` should be a `Entities\User` entity.'
        );
        
        $wrongEntity = new UserQuery();
        self::$DI['app']['model.user-manager']->delete($wrongEntity);
    }

    public function testUpdateUser()
    {
        $template = self::$DI['app']['model.user-manipulator']->createUser('template', 'password');
        self::$DI['app']['model.user-manager']->update($template);
        $user = self::$DI['app']['model.user-manipulator']->createUser('login', 'password');
        $user->setModelOf($template);
        self::$DI['app']['model.user-manager']->update($user);
        $this->assertNotNull($user->getPassword());
        $this->assertNotNull($user->getModelOf());
    }
    
    public function testInvalidUpdateUser()
    {
        $this->setExpectedException(
            'Alchemy\Phrasea\Exception\InvalidArgumentException',
            'Entity of type `Entities\UserQuery` should be a `Entities\User` entity.'
        );
        
        $wrongEntity = new UserQuery();
        self::$DI['app']['model.user-manager']->update($wrongEntity);
    }

    public function testUpdateTemplate()
    {
        $user = $this->getMock('Entities\User', array('getId', 'setModelOf', 'reset'));
        $user->expects($this->any())->method('getId')->will($this->returnValue(1));

        $ftpCredential = $this->getMock('Entities\FtpCredential');
        $ftpCredential->expects($this->once())->method('resetCredentials');

        $user->setFtpCredential($ftpCredential);

        $settings = $this->getMock('Doctrine\Common\Collections\ArrayCollection', array('clear'));
        $settings->expects($this->once())->method('clear');

        $user->setSettings($settings);

        $notifSettings = $this->getMock('Doctrine\Common\Collections\ArrayCollection', array('clear'));
        $notifSettings->expects($this->once())->method('clear');

        $user->setNotificationSettings($notifSettings);

        $template = $this->getMock('Entities\User', array('getId'));
        $template->expects($this->any())->method('getId')->will($this->returnValue(2));

        $user->expects($this->once())->method('reset');
        self::$DI['app']['model.user-manager']->onUpdateModel($user, $template);
    }

    public function testUpdatePassword()
    {
        $user = self::$DI['app']['model.user-manager']->create();
        self::$DI['app']['model.user-manager']->onUpdatePassword($user, $hashPass = uniqid());
        $this->assertNotNull($user->getPassword());
        $this->assertNotEquals($hashPass, $user->getPassword());
        $this->assertNotNull($user->getNonce());
    }

    public function testUpdateCountry()
    {
        $geonamesConnector = $this->getMockBuilder('Alchemy\Geonames\Connector')
                ->disableOriginalConstructor()
                ->getMock();

        $geoname = $this->getMockBuilder('Alchemy\Geonames\Geoname')
                ->disableOriginalConstructor()
                ->getMock();

        $geoname->expects($this->once())
                ->method('get')
                ->with($this->equalTo('country'))
                ->will($this->returnValue(array('code' => 'fr')));

        $geonamesConnector->expects($this->once())
                ->method('geoname')
                ->will($this->returnValue($geoname));

        $userManager = new UserManager(
            $this->getMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface'),
            $geonamesConnector,
            self::$DI['app']['EM'],
            self::$DI['app']['phraseanet.appbox']
        );

        $user = self::$DI['app']['model.user-manager']->create();
        $user->setGeonameId(4);
        $userManager->onUpdateGeonameId($user);
        $this->assertEquals('fr', $user->getCountry());
    }

    public function testCleanSettings()
    {
        self::$DI['app']['model.user-manipulator']->createUser('login', 'toto');
        $user = self::$DI['app']['model.user-manipulator']->getRepository()->findOneByLogin('login');
        $this->assertGreaterThan(0, $user->getSettings()->count());
        self::$DI['app']['model.user-manager']->cleanSettings($user);
        self::$DI['app']['model.user-manager']->update($user);
        $user = self::$DI['app']['model.user-manipulator']->getRepository()->findOneByLogin('login');
        $this->assertEquals(0, $user->getSettings()->count());
    }

    public function testCleanQueries()
    {
        $user = self::$DI['app']['model.user-manipulator']->createUser('login', 'toto');
        $userQuery = new UserQuery();
        $userQuery->setUser($user);
        $userQuery->setQuery('blabla');
        $user->setQueries(new ArrayCollection(array($userQuery)));
        self::$DI['app']['model.user-manager']->update($user);
        $user = self::$DI['app']['model.user-manipulator']->getRepository()->findOneByLogin('login');
        $this->assertGreaterThan(0, $user->getQueries()->count());
        self::$DI['app']['model.user-manager']->cleanQueries($user);
        self::$DI['app']['model.user-manager']->update($user);
        $user = self::$DI['app']['model.user-manipulator']->getRepository()->findOneByLogin('login');
        $this->assertEquals(0, $user->getQueries()->count());
    }
}
