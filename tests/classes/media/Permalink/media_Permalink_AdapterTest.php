<?php

class media_Permalink_AdapterTest extends \PhraseanetTestCase
{
    /**
     * @var media_Permalink_Adapter
     */
    public static $object;
    public static $subdef;

    public function setUp()
    {
        parent::setUp();
        $databox = self::$DI['record_1']->get_databox();
        self::$subdef = self::$DI['record_1']->get_subdef('document');
        self::$object = media_Permalink_Adapter::getPermalink(self::$DI['app'], $databox, self::$subdef);
    }

    public function testGet_label()
    {
        $this->assertInternalType('string', self::$object->get_label());
        $this->assertEquals('test001', self::$object->get_label());
    }

    public function testGetPermalink()
    {
        $this->assertTrue((self::$object instanceof media_Permalink_Adapter));
    }

    public function testSet_is_activated()
    {
        self::$object->set_is_activated(true);
        $this->assertTrue(self::$object->get_is_activated());
        self::$object->set_is_activated(false);
        $this->assertFalse(self::$object->get_is_activated());
        self::$object->set_is_activated(true);
        $this->assertTrue(self::$object->get_is_activated());
    }

    public function testSet_label()
    {
        self::$object->set_label('coucou les chicos');
        $this->assertEquals('coucou-les-chicos', self::$object->get_label());
        self::$object->set_label('');
        $this->assertEquals('untitled', self::$object->get_label());
        self::$object->set_label('JE ANp    ra&é"\/,;:!§/.?%µ*ù$]@^\[{#~234567890°+\'(-è_çà');
        $this->assertEquals('JE-ANp-raeu234567890-e_ca', self::$object->get_label());
    }

    public function testGet_url()
    {
        $url = rtrim(self::$DI['app']['phraseanet.registry']->get('GV_ServerName'), '/') . '/permalink/v1/'
            . self::$DI['record_1']->get_sbas_id() . '/'
            . self::$DI['record_1']->get_record_id()
            . '/document/' . self::$object->get_label()
            . '.' . pathinfo(self::$DI['record_1']->get_subdef('document')->get_file(), PATHINFO_EXTENSION)
            . '?token=' . self::$object->get_token();

        $this->assertEquals($url, self::$object->get_url());
    }

    public function testGet_Previewurl()
    {
        $databox = self::$DI['record_1']->get_databox();
        $subdef = self::$DI['record_1']->get_subdef('preview');
        $previewPermalink = media_Permalink_Adapter::getPermalink(self::$DI['app'], $databox, $subdef);

        $url = rtrim(self::$DI['app']['phraseanet.registry']->get('GV_ServerName'), '/') . '/permalink/v1/'
            . self::$DI['record_1']->get_sbas_id() . '/'
            . self::$DI['record_1']->get_record_id()
            . '/preview/' . $previewPermalink->get_label()
            . '.' . pathinfo(self::$DI['record_1']->get_subdef('preview')->get_file(), PATHINFO_EXTENSION)
            . '?token=' . $previewPermalink->get_token();

        $this->assertEquals($url, $previewPermalink->get_url());
    }

    public function testGet_page()
    {
        $url = rtrim(self::$DI['app']['phraseanet.registry']->get('GV_ServerName'), '/') . '/permalink/v1/'
            . self::$DI['record_1']->get_sbas_id() . '/'
            . self::$DI['record_1']->get_record_id()
            . '/document/'
            . '?token=' . self::$object->get_token();

        $this->assertEquals($url, self::$object->get_page());
    }

    public function testGet_id()
    {
        $this->assertInternalType('integer', self::$object->get_id());
    }

    public function testGet_token()
    {
        $this->assertInternalType('string', self::$object->get_token());
    }

    public function testGet_is_activated()
    {
        $this->assertInternalType('boolean', self::$object->get_is_activated());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', self::$object->get_created_on());
    }

    public function testGet_last_modified()
    {
        $this->assertInstanceOf('DateTime', self::$object->get_last_modified());
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testCreateAPermalinkAlreadyCreated()
    {
        $databox = self::$DI['record_1']->get_databox();
        media_Permalink_Adapter::create(self::$DI['app'], $databox, self::$DI['record_1']->get_subdef('document'));
    }
}
