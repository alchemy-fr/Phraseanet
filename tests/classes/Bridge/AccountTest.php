<?php

require_once __DIR__ . '/Bridge_datas.inc';

class Bridge_AccountTest extends \PhraseanetTestCase
{
    /**
     * @var Bridge_Account
     */
    private static $object;
    private static $api;
    private static $dist_id;
    private static $named;
    private static $id;

    public function setUp()
    {
        parent::setup();

        if (!self::$object) {
            self::$DI['user'];
            $sql = 'DELETE FROM bridge_apis WHERE name = "Apitest"';
            $stmt = self::$DI['app']['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            self::$api = Bridge_Api::create(self::$DI['app'], 'Apitest');
            self::$dist_id = 'EZ1565loPP';
            self::$named = 'Fête à pinpins';

            $account = Bridge_Account::create(
                self::$DI['app'],
                self::$api,
                self::$DI['user'],
                self::$dist_id,
                self::$named
            );

            self::$id = $account->get_id();

            self::$object = new Bridge_Account(self::$DI['app'], self::$api, self::$id);
        }
    }

    public static function tearDownAfterClass()
    {
        if (self::$object) {
            self::$object->delete();
            self::$object = null;
        }

        if (self::$api) {
            self::$api->delete();
            self::$api = null;
        }

        self::$object = self::$api = self::$dist_id = self::$named = self::$id = null;

        parent::tearDownAfterClass();
    }

    public function testGet_id()
    {
        $this->assertTrue(is_int(self::$object->get_id()));
        $this->assertEquals(self::$id, self::$object->get_id());
    }

    public function testGet_api()
    {
        $this->assertInstanceOf('Bridge_Api', self::$object->get_api());
        $this->assertEquals(self::$api, self::$object->get_api());
        $this->assertEquals(self::$api->get_id(), self::$object->get_api()->get_id());
    }

    public function testGet_dist_id()
    {
        $this->assertEquals(self::$dist_id, self::$object->get_dist_id());
    }

    public function testGet_user()
    {
        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\User', self::$object->get_user());
        $this->assertEquals(self::$DI['user']->getId(), self::$object->get_user()->getId());
    }

    public function testGet_name()
    {
        $this->assertEquals(self::$named, self::$object->get_name());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', self::$object->get_created_on());
        $this->assertTrue(self::$object->get_created_on() <= new DateTime());
    }

    public function testGet_updated_on()
    {
        $this->assertInstanceOf('DateTime', self::$object->get_updated_on());
        $this->assertTrue(self::$object->get_updated_on() <= new DateTime());
        $this->assertTrue(self::$object->get_updated_on() >= self::$object->get_created_on());

        $update1 = self::$object->get_updated_on();
        sleep(2);
        self::$object->set_name('prout');

        $update2 = self::$object->get_updated_on();
        $this->assertTrue($update2 > $update1);
    }

    public function testSet_name()
    {
        $new_name = 'YODELALI &é"\'(-è_çà)';
        self::$object->set_name($new_name);
        $this->assertEquals($new_name, self::$object->get_name());
        $new_name = 'BACHI BOUZOUKS';
        self::$object->set_name($new_name);
        $this->assertEquals($new_name, self::$object->get_name());
    }

    public function testGet_accounts_by_api()
    {
        $accounts = Bridge_Account::get_accounts_by_api(self::$DI['app'], self::$api);
        $this->assertTrue(is_array($accounts));

        $this->assertGreaterThan(0, count($accounts));

        foreach ($accounts as $account) {
            $this->assertInstanceOf('Bridge_Account', $account);
        }
    }

    public function testGet_settings()
    {
        $this->assertInstanceOf('Bridge_AccountSettings', self::$object->get_settings());
    }

    public function testGet_accounts_by_user()
    {
        $accounts = Bridge_Account::get_accounts_by_user(self::$DI['app'], self::$DI['user']);

        $this->assertTrue(is_array($accounts));
        $this->assertTrue(count($accounts) > 0);

        foreach ($accounts as $account) {
            $this->assertInstanceOf('Bridge_Account', $account);
        }
    }

    public function testLoad_account()
    {
        $account = Bridge_Account::load_account(self::$DI['app'], self::$object->get_id());
        $this->assertEquals(self::$object->get_id(), $account->get_id());
    }
}
