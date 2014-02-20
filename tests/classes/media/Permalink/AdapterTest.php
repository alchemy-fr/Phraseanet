<?php

class media_Permalink_AdapterTest extends \PhraseanetTestCase
{
    /**
     * @var media_Permalink_Adapter
     */
    private $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = media_Permalink_Adapter::getPermalink(self::$DI['app'], self::$DI['record_1']->get_databox(), self::$DI['record_1']->get_subdef('document'));
    }

    public function testGetPermalink()
    {
        $this->assertTrue(($this->object instanceof media_Permalink_Adapter));
    }

    public function testSet_is_activated()
    {
        $this->object->set_is_activated(true);
        $this->assertTrue($this->object->get_is_activated());
        $this->object->set_is_activated(false);
        $this->assertFalse($this->object->get_is_activated());
        $this->object->set_is_activated(true);
        $this->assertTrue($this->object->get_is_activated());
    }

    public function testGettersAndSetters()
    {
        $this->object->set_label('coucou les chicos');
        $this->assertEquals('coucou-les-chicos', $this->object->get_label());
        $this->object->set_label('');
        $this->assertEquals('untitled', $this->object->get_label());
        $this->object->set_label('JE ANp    ra&é"\/,;:!§/.?%µ*ù$]@^\[{#~234567890°+\'(-è_çà');
        $this->assertEquals('JE-ANp-raeu234567890-e_ca', $this->object->get_label());
    }

    public function testGet_url()
    {
        $url = rtrim(self::$DI['app']['conf']->get('servername'), '/') . '/permalink/v1/'
            . self::$DI['record_1']->get_sbas_id() . '/'
            . self::$DI['record_1']->get_record_id()
            . '/document/' . $this->object->get_label()
            . '.' . pathinfo(self::$DI['record_1']->get_subdef('document')->get_file(), PATHINFO_EXTENSION)
            . '?token=' . $this->object->get_token();

        $this->assertEquals($url, $this->object->get_url());
    }

    public function testGet_Previewurl()
    {
        $databox = self::$DI['record_1']->get_databox();
        $subdef = self::$DI['record_1']->get_subdef('preview');
        $previewPermalink = media_Permalink_Adapter::getPermalink(self::$DI['app'], $databox, $subdef);

        $url = rtrim(self::$DI['app']['conf']->get('servername'), '/') . '/permalink/v1/'
            . self::$DI['record_1']->get_sbas_id() . '/'
            . self::$DI['record_1']->get_record_id()
            . '/preview/' . $previewPermalink->get_label()
            . '.' . pathinfo(self::$DI['record_1']->get_subdef('preview')->get_file(), PATHINFO_EXTENSION)
            . '?token=' . $previewPermalink->get_token();

        $this->assertEquals($url, $previewPermalink->get_url());
    }

    public function testGet_page()
    {
        $url = rtrim(self::$DI['app']['conf']->get('servername'), '/') . '/permalink/v1/'
            . self::$DI['record_1']->get_sbas_id() . '/'
            . self::$DI['record_1']->get_record_id()
            . '/document/'
            . '?token=' . $this->object->get_token();

        $this->assertEquals($url, $this->object->get_page());
    }

    public function testGet_id()
    {
        $this->assertInternalType('integer', $this->object->get_id());
    }

    public function testGet_token()
    {
        $this->assertInternalType('string', $this->object->get_token());
    }

    public function testGet_is_activated()
    {
        $this->assertInternalType('boolean', $this->object->get_is_activated());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_created_on());
    }

    public function testGet_last_modified()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_last_modified());
    }

    /**
     * @expectedException \Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testCreateAPermalinkAlreadyCreated()
    {
        media_Permalink_Adapter::create(self::$DI['app'], self::$DI['record_1']->get_databox(), self::$DI['record_1']->get_subdef('document'));
    }
}
