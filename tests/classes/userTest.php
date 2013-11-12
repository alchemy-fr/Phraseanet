<?php

class userTest extends PhraseanetPHPUnitAbstract
{
    public function testMail()
    {
        $this->assertFalse(User_Adapter::get_usr_id_from_email(self::$DI['app'], null));
        try {
            self::$DI['user']->set_email(null);

            $this->assertFalse(User_Adapter::get_usr_id_from_email(self::$DI['app'], null));
            self::$DI['user']->set_email('');
            $this->assertFalse(User_Adapter::get_usr_id_from_email(self::$DI['app'], null));
            self::$DI['user']->set_email('noone@example.com');
            $this->assertEquals(self::$DI['user']->get_id(), User_Adapter::get_usr_id_from_email(self::$DI['app'], 'noone@example.com'));
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
        try {

            self::$DI['user']->set_email('noonealt1@example.com');
            $this->fail('A user already got this address');
        } catch (Exception $e) {

        }
        $this->assertFalse(User_Adapter::get_usr_id_from_email(self::$DI['app'], null));
    }

    public function testDeleteSetMailToNullAndRemovesProviders()
    {
        try {
            $usrId = \User_Adapter::get_usr_id_from_login(self::$DI['app'], 'test_phpunit_providers');
            $user = \User_Adapter::getInstance($usrId, self::$DI['app']);
        } catch (\Exception $e) {
            $user = \User_Adapter::create(self::$DI['app'], 'test_phpunit_providers', 'any', null, false);
        }

        $provider = new Alchemy\Phrasea\Model\Entities\UsrAuthProvider();
        $provider->setDistantId(12345);
        $provider->setProvider('custom-one');
        $provider->setUsrId($user->get_id());

        self::$DI['app']['EM']->persist($provider);
        self::$DI['app']['EM']->flush();

        $user->delete();

        $repo = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrAuthProvider');
        $this->assertNull($repo->findWithProviderAndId('custom-one', 12345));
    }

    public function testGetPref()
    {
        $user = $this->get_user();

        $this->assertNull($user->getPrefs('lalala'));
        $this->assertSame('popo', $user->getPrefs('lalala', 'popo'));
        $this->assertSame(\User_Adapter::$def_values['editing_top_box'], $user->getPrefs('editing_top_box'));
    }

    public function testSetPref()
    {
        $user = $this->get_user();

        $user->setPrefs('prout', 'pooop');
        $this->assertSame('pooop', $user->getPrefs('prout'));
    }

    public function testGetNotificationPref()
    {
        $user = $this->get_user();

        $this->assertSame('1', $user->get_notifications_preference(self::$DI['app'], 'eventsmanager_notify_push'));
    }

    public function testNotificationPref()
    {
        $user = $this->get_user();

        $this->assertSame('1', $user->get_notifications_preference(self::$DI['app'], 'eventsmanager_notify_push'));
        $user->set_notification_preference(self::$DI['app'], 'eventsmanager_notify_push', false);
        $this->assertSame('0', $user->get_notifications_preference(self::$DI['app'], 'eventsmanager_notify_push'));
        $user->set_notification_preference(self::$DI['app'], 'eventsmanager_notify_push', true);
        $this->assertSame('1', $user->get_notifications_preference(self::$DI['app'], 'eventsmanager_notify_push'));
    }

    private function get_user()
    {
        $usr_id = \User_Adapter::get_usr_id_from_login(self::$DI['app'], 'notif_ref_test');
        if ($usr_id) {
            $user = \User_Adapter::getInstance($usr_id, self::$DI['app']);
            $user->delete();
        }

        return \User_Adapter::create(self::$DI['app'], 'notif_ref_test', mt_rand(), null, false);
    }
}
