<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class media_Permalink_AdapterTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var media_Permalink_Adapter
     */
    static $object;
    protected static $need_records = true;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $databox = self::$record_1->get_databox();
        static::$object = media_Permalink_Adapter::getPermalink($databox, self::$record_1->get_subdef('document'));
    }

    public function testGetPermalink()
    {
        $this->assertTrue((static::$object instanceof media_Permalink_Adapter));
    }

    public function testSet_is_activated()
    {
        static::$object->set_is_activated(true);
        $this->assertTrue(static::$object->get_is_activated());
        static::$object->set_is_activated(false);
        $this->assertFalse(static::$object->get_is_activated());
        static::$object->set_is_activated(true);
        $this->assertTrue(static::$object->get_is_activated());
    }

    public function testSet_label()
    {
        static::$object->set_label('coucou les chicos');
        $this->assertEquals('coucou-les-chicos', static::$object->get_label());
        static::$object->set_label('');
        $this->assertEquals('', static::$object->get_label());
        static::$object->set_label('JE ANp    ra&é"\/,;:!§/.?%µ*ù$]@^\[{#~234567890°+\'(-è_çà');
        $this->assertEquals('JE-ANp-raeu234567890-e_ca', static::$object->get_label());
    }

    public function testGet_url()
    {
        $registry = registry::get_instance();
        $url = $registry->get('GV_ServerName') . 'permalink/v1/' . static::$object->get_label() . '/' . self::$record_1->get_sbas_id() . '/' . self::$record_1->get_record_id() . '/' .
            static::$object->get_token() . '/document/';

        $this->assertEquals($url, static::$object->get_url($registry));
    }

    /**
     * @todo Implement testGet_page().
     */
    public function testGet_page()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGet_id().
     */
    public function testGet_id()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGet_token().
     */
    public function testGet_token()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGet_is_activated().
     */
    public function testGet_is_activated()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
        $this->assertTrue(is_bool(static::$object->get_is_activated));
    }

    /**
     * @todo Implement testGet_created_on().
     */
    public function testGet_created_on()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGet_last_modified().
     */
    public function testGet_last_modified()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGet_label().
     */
    public function testGet_label()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testCreate().
     */
    public function testCreate()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
