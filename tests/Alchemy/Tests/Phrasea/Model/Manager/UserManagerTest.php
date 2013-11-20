<?php

namespace Alchemy\Tests\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Entities\UserNotificationSetting;
use Alchemy\Phrasea\Model\Entities\UserQuery;
use Alchemy\Phrasea\Model\Entities\UserSetting;

class UserManagerTest extends \PhraseanetPHPUnitAbstract
{
    public function testNewUser()
    {
        $user = self::$DI['app']['model.user-manager']->create();
        $this->assertInstanceOf('\Alchemy\Phrasea\Model\Entities\User', $user);
    }

    public function testDeleteUser()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'password');
        $query = new UserQuery();
        $query->setUser($user);
        $query->setQuery('a query');
        $query->setUsrId(1);
        $user->addQuery($query);
        $setting = new UserSetting();
        $setting->setUsrId(1);
        $setting->setUser($user);
        $setting->setName('setting');
        $setting->setValue(0);
        $user->addSetting($setting);
        $setting = new UserNotificationSetting();
        $setting->setUsrId(1);
        $setting->setUser($user);
        $setting->setName('setting');
        $setting->setValue(0);
        $user->addNotificationSettings($setting);
        self::$DI['app']['model.user-manager']->update($user);
        self::$DI['app']['model.user-manager']->delete($user);
        $this->assertTrue($user->isDeleted());
        $this->assertNull($user->getEmail());
        $this->assertEquals('(#deleted_', substr($user->getLogin(), 0, 10));
        $user = self::$DI['app']['manipulator.user']->getRepository()->findOneByLogin('(#deleted_login');
        $this->assertEquals(0, $user->getSettings()->count());
        $this->assertEquals(0, $user->getNotificationSettings()->count());
        $this->assertEquals(0, $user->getQueries()->count());
    }

    public function testUpdateUser()
    {
        $template = self::$DI['app']['manipulator.user']->createUser('template', 'password');
        self::$DI['app']['model.user-manager']->update($template);
        $user = self::$DI['app']['manipulator.user']->createUser('login', 'password');
        $user->setModelOf($template);
        self::$DI['app']['model.user-manager']->update($user);
        $this->assertNotNull($user->getPassword());
        $this->assertNotNull($user->getModelOf());
    }
}
