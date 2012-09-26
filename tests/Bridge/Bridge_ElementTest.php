<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/Bridge_datas.inc';

class Bridge_ElementTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var Bridge_Element
     */
    protected $object;
    protected $account;
    protected $api;
    protected $dist_id;
    protected $named;
    protected $id;
    protected $title;
    protected $status;

    public function setUp()
    {
        parent::setUp();
        $appbox = self::$DI['app']['phraseanet.appbox'];

        $sql = 'DELETE FROM bridge_apis WHERE name = "Apitest"';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $this->api = Bridge_Api::create(self::$DI['app'], 'Apitest');
        $this->dist_id = 'EZ1565loPP';
        $this->named = 'Fête à pinpins';
        $this->account = Bridge_Account::create(self::$DI['app'], $this->api, self::$DI['user'], $this->dist_id, $this->named);

        $this->title = 'GOGACKO';
        $this->status = 'Processing';

        $element = Bridge_Element::create(self::$DI['app'], $this->account, self::$DI['record_1'], $this->title, $this->status, $this->account->get_api()->get_connector()->get_default_element_type());
        $this->id = $element->get_id();
        $this->object = new Bridge_Element(self::$DI['app'], $this->account, $this->id);
    }

    public function tearDown()
    {
        if ($this->object) {
            $this->object->delete();
        }

        try {
            new Bridge_Element(self::$DI['app'], $this->account, $this->id);
            $this->fail();
        } catch (Bridge_Exception_ElementNotFound $e) {

        }
        if ($this->api) {
            $this->api->delete();
        }
        parent::tearDown();
    }

    public function testGet_account()
    {
        $this->assertInstanceOf('Bridge_Account', $this->object->get_account());
        $this->assertEquals($this->account, $this->object->get_account());
        $this->assertEquals($this->account->get_id(), $this->object->get_account()->get_id());
    }

    public function testGet_id()
    {

    }

    public function testGet_record()
    {
        $this->assertInstanceOf('record_adapter', $this->object->get_record());
        $this->assertEquals(self::$DI['record_1']->get_sbas_id(), $this->object->get_record()->get_sbas_id());
        $this->assertEquals(self::$DI['record_1']->get_record_id(), $this->object->get_record()->get_record_id());
    }

    public function testGet_dist_id()
    {
        $this->assertNull($this->object->get_dist_id());
    }

    public function testGet_status()
    {
        $this->assertEquals($this->status, $this->object->get_status());
    }

    public function testSet_status()
    {
        $update1 = $this->object->get_updated_on();
        $new_status = '&é"\'(-è_çà)';
        $this->object->set_status($new_status);
        $this->assertEquals($new_status, $this->object->get_status());
        sleep(1);
        $new_status = '&é"0687345àç_)à)';
        $this->object->set_status($new_status);
        $this->assertEquals($new_status, $this->object->get_status());
        $update2 = $this->object->get_updated_on();
        $this->assertTrue($update2 > $update1);
    }

    public function testGet_title()
    {
        $this->assertEquals($this->title, $this->object->get_title());
    }

    public function testGet_type()
    {
        $this->markTestIncomplete();
    }

    public function testSet_title()
    {
        $update1 = $this->object->get_updated_on();
        sleep(1);
        $new_title = 'Cigares du pharaon';
        $this->object->set_title($new_title);
        $this->assertEquals($new_title, $this->object->get_title());
        $update2 = $this->object->get_updated_on();
        $this->assertTrue($update2 > $update1);
    }

    public function testSet_distid()
    {
        $update1 = $this->object->get_updated_on();
        sleep(1);
        $this->object->set_dist_id($this->dist_id);
        $this->assertEquals($this->dist_id, $this->object->get_dist_id());
        $update2 = $this->object->get_updated_on();
        $this->assertTrue($update2 > $update1);
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_created_on());
    }

    public function testGet_updated_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_updated_on());
    }

    public function testGet_elements_by_account()
    {
        $elements = Bridge_Element::get_elements_by_account(self::$DI['app'], $this->account);
        $this->assertTrue(is_array($elements));
        $this->assertGreaterThan(0, count($elements));

        foreach ($elements as $element) {
            $this->assertInstanceOf('Bridge_Element', $element);
        }
    }

    public function testGet_connector_status()
    {
        $this->markTestIncomplete();
    }

    public function testSet_connector_status()
    {
        $this->markTestIncomplete();
    }

    public function testGet_datas()
    {
        $this->markTestIncomplete();
    }

    public function testSet_datas()
    {
        $this->markTestIncomplete();
    }

    public function test()
    {
        $this->markTestIncomplete();
    }
}
