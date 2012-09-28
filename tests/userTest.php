<?php

require_once __DIR__ . '/PhraseanetPHPUnitAbstract.class.inc';

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
}
