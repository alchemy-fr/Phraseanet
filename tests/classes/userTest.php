<?php

use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UsrAuthProvider;

class userTest extends \PhraseanetTestCase
{
    public function testMail()
    {
            self::$DI['user']->setEmail('');
            $this->assertNull(self::$DI['app']['manipulator.user']->getRepository()->findByEmail(self::$DI['user']->getEmail()));
            self::$DI['user']->setEmail('noone@example.com');
            $this->assertEquals(self::$DI['user'], self::$DI['app']['manipulator.user']->getRepository()->findByEmail('noone@example.com'));
        try {
            self::$DI['user']->setEmail('noonealt1@example.com');
            $this->fail('A user already got this address');
        } catch (Exception $e) {

        }
    }

    public function testDeleteSetMailToNullAndRemovesProviders()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('test_phpunit_providers', 'any');

        $provider = new UsrAuthProvider();
        $provider->setDistantId(12345);
        $provider->setProvider('custom-one');
        $provider->setUser($user);

        self::$DI['app']['EM']->persist($provider);
        self::$DI['app']['EM']->flush();

        self::$DI['app']['model.user-manager']->delete($user);

        $this->assertNull(self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrAuthProvider')->findWithProviderAndId('custom-one', 12345));
    }

    public function testDeleteSetMailToNullAndRemovesSessions()
    {
        try {
            $usrId = \User_Adapter::get_usr_id_from_login(self::$DI['app'], 'test_phpunit_sessions');
            $user = \User_Adapter::getInstance($usrId, self::$DI['app']);
        } catch (\Exception $e) {
            $user = \User_Adapter::create(self::$DI['app'], 'test_phpunit_sessions', 'any', null, false);
        }

        $session = new \Alchemy\Phrasea\Model\Entities\Session();
        $session
            ->setUsrId($user->get_id())
            ->setUserAgent('');

        self::$DI['app']['EM']->persist($session);
        self::$DI['app']['EM']->flush();

        $user->delete();

        $repo = self::$DI['app']['EM']->getRepository('Phraseanet:Session');
        $this->assertCount(0, $repo->findByUser($user));
    }

    public function testGetPref()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('notif_ref_test', 'pass');

        $this->assertNull($user->getSettingValue('lalala'));
        $this->assertSame('popo', $user->getSettingValue('lalala', 'popo'));
        $this->assertSame(User::$defaultUserSettings['editing_top_box'], $user->getSettingValue('editing_top_box'));
    }

    public function testGetPrefWithACustomizedConf()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('notif_ref_test', 'pass');

        $data = self::$DI['app']['conf']->get(['user-settings']);

        self::$DI['app']['conf']->set('user-settings', [
            'images_per_page' => 42,
            'images_size'     => 666,
            'lalala'          => 'didou',
        ]);

        $user = $this->get_user();
        $user->setPrefs('images_per_page', 35);

        $this->assertNull($user->getSettingValue('lalala'));
        $this->assertSame(666, $user->getSettingValue('images_size'));
        $this->assertSame(42, $user->getSettingValue('images_per_page'));
        $this->assertSame(User::$defaultUserSettings['editing_top_box'], $user->getSettingValue('editing_top_box'));

        if (null === $data) {
            self::$DI['app']['conf']->remove('user-settings');
        } else {
            self::$DI['app']['conf']->set('user-settings', $data);
        }
    }

    public function testSetPref()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('notif_ref_test', 'pass');

        $user->setSettingValue('prout', 'pooop');
        $this->assertSame('pooop', $user->getSettingValue('prout'));
    }

    public function testGetNotificationPref()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('notif_ref_test', 'pass');

        $this->assertTrue($user->getNotificationSettingValue('eventsmanager_notify_push'));
    }

    public function testNotificationPref()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('notif_ref_test', 'pass');

        $this->assertTrue($user->getNotificationSettingValue('eventsmanager_notify_push'));
        $user->setNotificationSettingValue('eventsmanager_notify_push', false);
        $this->assertFalse($user->getNotificationSettingValue('eventsmanager_notify_push'));
        $user->setNotificationSettingValue('eventsmanager_notify_push', true);
        $this->assertTrue($user->getNotificationSettingValue('eventsmanager_notify_push'));
    }
}
