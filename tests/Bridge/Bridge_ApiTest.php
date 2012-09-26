<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/Bridge_datas.inc';

class Bridge_ApiTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var Bridge_Api
     */
    protected $object;
    protected $id;
    protected $type;

    public function setUp()
    {
        parent::setUp();
        $appbox = self::$DI['app']['phraseanet.appbox'];

        $sql = 'DELETE FROM bridge_apis WHERE name = "Apitest"';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $this->type = 'Apitest';
        $api = Bridge_Api::create(self::$DI['app'], $this->type);

        $this->id = $api->get_id();
        $this->object = new Bridge_Api(self::$DI['app'], $api->get_id());
    }

    public function tearDown()
    {
        if ($this->object) {
            $this->object->delete();
        }
        try {
            new Bridge_Api(self::$DI['app'], $this->id);
            $this->fail();
        } catch (Bridge_Exception_ApiNotFound $e) {

        }
        parent::tearDown();
    }

    public function testGet_id()
    {
        $this->assertTrue(is_int($this->object->get_id()));
        $this->assertTrue($this->object->get_id() > 0);
        $this->assertEquals($this->id, $this->object->get_id());
    }

    public function testis_disabled()
    {
        $this->assertTrue(is_bool($this->object->is_disabled()));
        $this->assertFalse($this->object->is_disabled());
    }

    public function testenable()
    {
        $this->assertTrue(is_bool($this->object->is_disabled()));
        $this->assertFalse($this->object->is_disabled());
        sleep(1);
        $update1 = $this->object->get_updated_on();

        $this->object->disable(new DateTime('+2 seconds'));
        $this->assertTrue($this->object->is_disabled());
        sleep(3);
        $update2 = $this->object->get_updated_on();
        $this->assertTrue($update2 > $update1);
        $this->assertFalse($this->object->is_disabled());
        $this->object->enable();
        $this->assertFalse($this->object->is_disabled());
    }

    public function testdisable()
    {
        $this->testenable();
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
    }

    public function testGet_connector()
    {
        $this->markTestIncomplete();
    }

    public function testlist_elements()
    {
        $this->markTestIncomplete();
    }

    public function testlist_containers()
    {
        $this->markTestIncomplete();
    }

    public function testupdate_element()
    {
        $this->markTestIncomplete();
    }

    public function testcreate_container()
    {
        $this->markTestIncomplete();
    }

    public function testadd_element_to_container()
    {
        $this->markTestIncomplete();
    }

    public function testdelete_object()
    {
        $this->markTestIncomplete();
    }

    public function testacceptable_records()
    {
        $this->markTestIncomplete();
    }

    public function testget_element_from_id()
    {
        $this->markTestIncomplete();
    }

    public function testget_container_from_id()
    {
        $this->markTestIncomplete();
    }

    public function testget_category_list()
    {
        $this->markTestIncomplete();
    }

    public function testget_element_status()
    {
        $this->markTestIncomplete();
    }

    public function testmap_connector_to_element_status()
    {
        $this->markTestIncomplete();
    }

    public function testupload()
    {
        $this->markTestIncomplete();
    }

    public function testgenerate_callback_url()
    {
        $this->markTestIncomplete();
    }

    public function testgenerate_login_url()
    {
        $this->markTestIncomplete();
    }

    public function testget_connector_by_name()
    {
        $this->markTestIncomplete();
    }

    public function testget_by_api_name()
    {
        $this->markTestIncomplete();
    }

    public function testget_availables()
    {
        $this->markTestIncomplete();
    }
}
