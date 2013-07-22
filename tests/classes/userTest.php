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

        $provider = new Entities\UsrAuthProvider();
        $provider->setDistantId(12345);
        $provider->setProvider('custom-one');
        $provider->setUsrId($user->get_id());

        self::$DI['app']['EM']->persist($provider);
        self::$DI['app']['EM']->flush();

        $user->delete();

        $repo = self::$DI['app']['EM']->getRepository('Entities\UsrAuthProvider');
        $this->assertNull($repo->findWithProviderAndId('custom-one', 12345));
    }
}
