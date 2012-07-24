<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/Bridge_datas.inc';

class Bridge_AccountTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var Bridge_Account
     */
    protected $object;
    protected $api;
    protected $dist_id;
    protected $named;
    protected $id;

    public function setUp()
    {
        parent::setUp();
        try {
            $appbox = appbox::get_instance(\bootstrap::getCore());

            $sql = 'DELETE FROM bridge_apis WHERE name = "Apitest"';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();

            $this->api = Bridge_Api::create($appbox, 'Apitest');
            $this->dist_id = 'EZ1565loPP';
            $this->named = 'Fête à pinpins';
            $account = Bridge_Account::create($appbox, $this->api, self::$user, $this->dist_id, $this->named);
            $this->id = $account->get_id();

            $this->object = new Bridge_Account($appbox, $this->api, $this->id);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function tearDown()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $this->object->delete();

        try {
            new Bridge_Account($appbox, $this->api, $this->id);
            $this->fail();
        } catch (Bridge_Exception_AccountNotFound $e) {

        }

        $this->api->delete();
        parent::tearDown();
    }

    public function testGet_id()
    {
        $this->assertTrue(is_int($this->object->get_id()));
        $this->assertEquals($this->id, $this->object->get_id());
    }

    public function testGet_api()
    {
        $this->assertInstanceOf('Bridge_Api', $this->object->get_api());
        $this->assertEquals($this->api, $this->object->get_api());
        $this->assertEquals($this->api->get_id(), $this->object->get_api()->get_id());
    }

    public function testGet_dist_id()
    {
        $this->assertEquals($this->dist_id, $this->object->get_dist_id());
    }

    public function testGet_user()
    {
        $this->assertInstanceOf('User_Adapter', $this->object->get_user());
        $this->assertEquals(self::$user->get_id(), $this->object->get_user()->get_id());
    }

    public function testGet_name()
    {
        $this->assertEquals($this->named, $this->object->get_name());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_created_on());
        $this->assertTrue($this->object->get_created_on() <= new DateTime());
    }

    public function testGet_updated_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_updated_on());
        $this->assertTrue($this->object->get_updated_on() <= new DateTime());
        $this->assertTrue($this->object->get_updated_on() >= $this->object->get_created_on());

        $update1 = $this->object->get_updated_on();
        sleep(2);
        $this->object->set_name('prout');

        $update2 = $this->object->get_updated_on();
        $this->assertTrue($update2 > $update1);
    }

    public function testSet_name()
    {
        $new_name = 'YODELALI &é"\'(-è_çà)';
        $this->object->set_name($new_name);
        $this->assertEquals($new_name, $this->object->get_name());
        $new_name = 'BACHI BOUZOUKS';
        $this->object->set_name($new_name);
        $this->assertEquals($new_name, $this->object->get_name());
    }

    public function testGet_accounts_by_api()
    {
        $accounts = Bridge_Account::get_accounts_by_api(appbox::get_instance(\bootstrap::getCore()), $this->api);
        $this->assertTrue(is_array($accounts));

        $this->assertGreaterThan(0, count($accounts));

        foreach ($accounts as $account) {
            $this->assertInstanceOf('Bridge_Account', $account);
        }
    }

    public function testGet_settings()
    {
        $this->assertInstanceOf('Bridge_AccountSettings', $this->object->get_settings());
    }

    public function testGet_accounts_by_user()
    {
        $accounts = Bridge_Account::get_accounts_by_user(appbox::get_instance(\bootstrap::getCore()), self::$user);

        $this->assertTrue(is_array($accounts));
        $this->assertTrue(count($accounts) > 0);

        foreach ($accounts as $account) {
            $this->assertInstanceOf('Bridge_Account', $account);
        }
    }

    public function testLoad_account()
    {
        $account = Bridge_Account::load_account(appbox::get_instance(\bootstrap::getCore()), $this->object->get_id());
        $this->assertEquals($this->object->get_id(), $account->get_id());
    }

    public function testLoad_account_from_distant_id()
    {
        $this->markTestIncomplete();
    }
}
