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

        self::$DI['app']['manipulator.user']->delete($user);

        $this->assertNull(self::$DI['app']['EM']->getRepository('Phraseanet:UsrAuthProvider')->findWithProviderAndId('custom-one', 12345));
    }

    public function testDeleteSetMailToNullAndRemovesSessions()
    {
        if (null === $user = self::$DI['app']['manipulator.user']->getRepository()->findByLogin('test_phpunit_sessions')) {
            $user = self::$DI['app']['manipulator.user']->createUser('test_phpunit_sessions', \random::generatePassword());
        }

        $session = new \Alchemy\Phrasea\Model\Entities\Session();
        $session->setUser($user)->setUserAgent('');

        self::$DI['app']['EM']->persist($session);
        self::$DI['app']['EM']->flush();

        self::$DI['app']['manipulator.user']->delete($user);

        $repo = self::$DI['app']['EM']->getRepository('Phraseanet:Session');
        $this->assertCount(0, $repo->findByUser($user));
    }

    public function testGetPref()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('notif_ref_test', 'pass');

        $this->assertNull(self::$DI['app']['settings']->getUserSetting($user, 'lalala'));
        $this->assertSame('popo', self::$DI['app']['settings']->getUserSetting($user, 'lalala', 'popo'));
        $this->assertSame(self::$DI['app']['settings']->getUsersSettings()['editing_top_box'], self::$DI['app']['settings']->getUserSetting($user, 'editing_top_box'));
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

        self::$DI['app']['manipulator.user']->setUserSetting($user,'images_per_page', 35);

        $this->assertNull(self::$DI['app']['settings']->getUserSetting($user, 'lalala'));
        $this->assertSame(666, self::$DI['app']['settings']->getUserSetting($user, 'images_size'));
        $this->assertSame(35, self::$DI['app']['settings']->getUserSetting($user, 'images_per_page'));
        $this->assertSame(self::$DI['app']['settings']->getUsersSettings()['editing_top_box'], self::$DI['app']['settings']->getUserSetting($user, 'editing_top_box'));

        if (null === $data) {
            self::$DI['app']['conf']->remove('user-settings');
        } else {
            self::$DI['app']['conf']->set('user-settings', $data);
        }
    }

    public function testSetPref()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('notif_ref_test', 'pass');

        self::$DI['app']['manipulator.user']->setUserSetting($user, 'prout', 'pooop');
        $this->assertSame('pooop', self::$DI['app']['settings']->getUserSetting($user, 'prout'));
    }

    public function testGetNotificationPref()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('notif_ref_test', 'pass');

        $this->assertTrue(self::$DI['app']['settings']->getUserNotificationSetting($user, 'eventsmanager_notify_push'));
    }

    public function testNotificationPref()
    {
        $user = self::$DI['app']['manipulator.user']->createUser('notif_ref_test', 'pass');

        $this->assertTrue(self::$DI['app']['settings']->getUserNotificationSetting($user, 'eventsmanager_notify_push'));
        self::$DI['app']['manipulator.user']->setNotificationSetting($user, 'eventsmanager_notify_push', false);
        $this->assertFalse(self::$DI['app']['settings']->getUserNotificationSetting($user, 'eventsmanager_notify_push'));
        self::$DI['app']['manipulator.user']->setNotificationSetting($user, 'eventsmanager_notify_push', true);
        $this->assertTrue(self::$DI['app']['settings']->getUserNotificationSetting($user, 'eventsmanager_notify_push'));
    }
}
