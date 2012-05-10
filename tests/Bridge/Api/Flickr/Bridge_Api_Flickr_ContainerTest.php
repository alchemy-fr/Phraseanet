<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../../Bridge_datas.inc';

class Bridge_Api_Flickr_ContainerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Bridge_Api_Flickr_Container
     */
    protected $object;

    public function setUp()
    {
        $string = '
      <photoset id="72157626216528324" primary="5504567858" secret="017804c585" server="5174" farm="6" photos="22" videos="0" count_views="137" count_comments="0" can_comment="1" date_create="1299514498" date_update="1300335009">
        <title>Avis Blanche</title>
        <description>My Grandma\'s Recipe File.</description>
      </photoset>
    ';
        $xml = simplexml_load_string($string);
        $this->object = new Bridge_Api_Flickr_Container($xml, 'userid123', "photoset", "my_humbnail");
    }

    public function testGet_id()
    {
        $this->assertEquals("72157626216528324", $this->object->get_id());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_id());
    }

    public function testGet_thumbnail()
    {
        $this->assertEquals("my_humbnail", $this->object->get_thumbnail());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_thumbnail());
    }

    public function testGet_url()
    {
        $this->assertRegExp("/https:\/\/secure.flickr.com\/photos/", $this->object->get_url());
        $this->assertRegExp("/userid123/", $this->object->get_url());
        $this->assertRegExp("/72157626216528324/", $this->object->get_url());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_thumbnail());
    }

    public function testGet_title()
    {
        $this->assertEquals("My Grandma's Recipe File.", $this->object->get_description());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_description());
    }

    public function testGet_description()
    {
        $this->assertEquals("Avis Blanche", $this->object->get_title());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_title());
    }

    public function testGet_updated_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_updated_on());
        $this->assertEquals(DateTime::createFromFormat('U', '1300335009'), $this->object->get_updated_on());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', $this->object->get_created_on());
        $this->assertEquals(DateTime::createFromFormat('U', '1299514498'), $this->object->get_created_on());
    }

    public function testGet_type()
    {
        $this->assertEquals("photoset", $this->object->get_type());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_type());
    }
}
