<?php

namespace Alchemy\Tests\Phrasea\Model\Manager;

class UserManagerTest extends \PhraseanetTestCase
{
    public function testNewUser()
    {
        $user = self::$DI['app']['model.user-manager']->create();
        $this->assertInstanceOf('\Alchemy\Phrasea\Model\Entities\User', $user);
    }

    public function testDeleteUser()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'password');
        self::$DI['app']['manipulator.user']->logQuery($user, 'a query');
        self::$DI['app']['manipulator.user']->setUserSetting($user, 'setting', false);
        self::$DI['app']['manipulator.user']->setNotificationSetting($user, 'setting', false);
        self::$DI['app']['model.user-manager']->delete($user);
        $user = self::$DI['app']['manipulator.user']->getRepository()->findOneByLogin('login');
        $this->assertEquals(0, $user->getSettings()->count());
        $this->assertEquals(0, $user->getNotificationSettings()->count());
        $this->assertEquals(0, $user->getQueries()->count());
    }

    public function testUpdateUser()
    {
        $template = self::$DI['app']['manipulator.user']->createUser('template'.uniqid(), 'password');
        self::$DI['app']['model.user-manager']->update($template);
        $user = self::$DI['app']['manipulator.user']->createUser('login'.uniqid(), 'password');
        $user->setTemplateOwner($template);
        self::$DI['app']['model.user-manager']->update($user);
        $this->assertNotNull($user->getPassword());
        $this->assertNotNull($user->getTemplateOwner());
    }
}
