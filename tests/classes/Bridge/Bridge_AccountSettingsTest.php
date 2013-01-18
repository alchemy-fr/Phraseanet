<?php

require_once __DIR__ . '/Bridge_datas.inc';

class Bridge_AccountSettingsTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var Bridge_AccountSettings
     */
    protected $object;
    protected $account;
    protected $api;
    protected $dist_id;
    protected $named;

    public function setUp()
    {
        parent::setUp();
        try {

            $sql = 'DELETE FROM bridge_apis WHERE name = "Apitest"';
            $stmt = self::$DI['app']['phraseanet.appbox']->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
            $this->api = Bridge_Api::create(self::$DI['app'], 'Apitest');
            $this->dist_id = 'EZ1565loPP';
            $this->named = 'Fête à pinpins';
            $this->account = Bridge_Account::create(self::$DI['app'], $this->api, self::$DI['user'], $this->dist_id, $this->named);

            $this->object = new Bridge_AccountSettings(self::$DI['app']['phraseanet.appbox'], $this->account);
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function tearDown()
    {
        if ($this->api) {
            $this->api->delete();
        }
        parent::tearDown();
    }

    public function testGet()
    {
        $this->assertNull($this->object->get('test'));
        $this->assertEquals('caca', $this->object->get('test', 'caca'));
        $obj = new DateTime();
        $this->assertEquals($obj, $this->object->get('test', $obj));
    }

    public function testSet()
    {
        $this->object->set('tip', 'top');
        $this->assertEquals('top', $this->object->get('tip'));
        $this->object->set('tip', 'tap');
        $this->assertEquals('tap', $this->object->get('tip'));
        $this->object->set('tip', null);
        $this->assertEquals(null, $this->object->get('tip'));
    }

    public function testDelete()
    {
        $this->object->set('tip', 'top');
        $this->assertEquals('top', $this->object->get('tip'));
        $this->object->delete('tip');
        $this->assertEquals(null, $this->object->get('tip'));
        $this->assertEquals('flop', $this->object->get('tip', 'flop'));
    }
}
