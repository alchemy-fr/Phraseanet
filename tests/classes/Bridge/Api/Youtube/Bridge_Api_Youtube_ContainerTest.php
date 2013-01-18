<?php

require_once __DIR__ . '/../../Bridge_datas.inc';

class Bridge_Api_Youtube_ContainerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Bridge_Api_Youtube_Container
     */
    protected $object;

    public function setUp()
    {
        $published = new Zend_Gdata_App_Extension_Published("2011-10-21 12:00:00");
        $updated = new Zend_Gdata_App_Extension_Updated("2011-10-21 12:20:00");
        $id = new Zend_Gdata_App_Extension_Id("Az2cv12");
        $entry = new Zend_Gdata_YouTube_PlaylistListEntry();
        $entry->setMajorProtocolVersion(2);
        $entry->setId($id);
        $entry->setTitle(new Zend_Gdata_App_Extension_Title("one title"));
        $entry->setUpdated($updated);
        $entry->setPublished($published);
        $entry->setLink(array(new Zend_Gdata_App_Extension_link("one url", "alternate")));
        $entry->setDescription(new Zend_Gdata_App_Extension_Summary("one description"));
        $this->object = new Bridge_Api_Youtube_Container($entry, 'playlist', 'my_thumbnail');
    }

    /**
     * @todo find a way to test getPlaylistId
     */
    public function testGet_thumbnail()
    {
        $this->assertEquals("my_thumbnail", $this->object->get_thumbnail());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_thumbnail());
    }

    public function testGet_url()
    {
        $this->assertEquals("one url", $this->object->get_url());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_url());
    }

    public function testGet_title()
    {
        $this->assertEquals("one title", $this->object->get_title());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_title());
    }

    public function testGet_description()
    {
        $this->assertEquals("one description", $this->object->get_description());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_description());
    }

    public function testGet_updated_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_updated_on());
        $this->assertEquals(new DateTime("2011-10-21 12:20:00"), $this->object->get_updated_on());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_created_on());
        $this->assertEquals(new DateTime("2011-10-21 12:00:00"), $this->object->get_created_on());
    }

    public function testGet_type()
    {
        $this->assertEquals("playlist", $this->object->get_type());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_type());
    }
}
