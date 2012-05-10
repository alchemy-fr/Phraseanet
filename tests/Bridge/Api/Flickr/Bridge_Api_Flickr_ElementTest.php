<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../../Bridge_datas.inc';

class Bridge_Api_Flickr_ElementTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Bridge_Api_Flickr_Element
     */
    protected $object_list;

    /**
     * @var Bridge_Api_Flickr_Element
     */
    protected $object_alone;
    protected $xml_list;
    protected $xml_alone;

    public function setUp()
    {
        $str = '
      <photo id="6263188755" width_t="100" height_t="67" url_t="http://farm7.static.flickr.com/6034/6263188755_2dca715798_t.jpg" width_sq="75" height_sq="75" url_sq="http://farm7.static.flickr.com/6034/6263188755_2dca715798_s.jpg" views="1" tags="" lastupdate="1319126343" ownername="Boontyp4" datetakengranularity="0" datetaken="2008-09-28 20:26:00" dateupload="1319117962" license="0" isfamily="0" isfriend="0" ispublic="1" title="un titre" farm="7" server="6034" secret="2dca715798" owner="60578095@N05">
        <description>une description</description>
      </photo>
      ';
        $string = '
      <rsp stat="ok">
      <photo id="5930279108" media="photo" views="1" rotation="0" safety_level="0" license="0" isfavorite="0" dateuploaded="1310477820" farm="7" server="6135" secret="c06196fbd8">
      <owner iconfarm="0" iconserver="0" location="" realname="" username="Boontyp4" nsid="60578095@N05"></owner>
      <title>Australia.gif</title>
      <description>drapeau de l\'australie</description>
      <visibility isfamily="0" isfriend="1" ispublic="0"></visibility>
      <dates lastupdate="1314975347" takengranularity="0" taken="2011-07-12 06:37:00" posted="1310477820"></dates>
      <permissions permaddmeta="2" permcomment="3"></permissions>
      <editability canaddmeta="1" cancomment="1"></editability>
      <publiceditability canaddmeta="0" cancomment="1"></publiceditability>
      <usage canshare="0" canprint="1" canblog="1" candownload="1"></usage>
      <comments>0</comments>
      <notes></notes>
      <people haspeople="0"></people>
      <tags>
        <tag>yo</tag>
      </tags>
      <urls>
        <url type="photopage">http://www.flickr.com/photos/bees/2733/</url>
      </urls>
      </photo>
      </rsp>
      ';
        $this->xml_alone = simplexml_load_string($string);
        $this->object_alone = new Bridge_Api_Flickr_Element($this->xml_alone, '12037949754@N01', 'album', false);
        $this->xml_list = simplexml_load_string($str);
        $this->object_list = new Bridge_Api_Flickr_Element($this->xml_list, '12037949754@N01', 'album', true);
    }

    public function testGet_id()
    {
        $this->assertEquals("5930279108", $this->object_alone->get_id());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_alone->get_id());
        $this->assertEquals("6263188755", $this->object_list->get_id());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_list->get_id());
    }

    public function testGet_url()
    {
        $this->assertRegExp("/6263188755/", $this->object_list->get_url());
        $this->assertRegExp("/album/", $this->object_list->get_url());
        $this->assertRegExp("/60578095@N05/", $this->object_list->get_url());
        $this->assertRegExp("/http:\/\/www.flickr.com\//", $this->object_list->get_url());
        $this->assertEquals("http://www.flickr.com/photos/bees/2733/", $this->object_alone->get_url());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_alone->get_url());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_list->get_url());
    }

    public function testGet_thumbnail()
    {
        $this->assertEquals("http://farm7.static.flickr.com/6034/6263188755_2dca715798_t.jpg", $this->object_list->get_thumbnail());
        $this->assertRegExp("/https:\/\/farm7.static.flickr.com/", $this->object_alone->get_thumbnail());
        $this->assertRegExp("/6135/", $this->object_alone->get_thumbnail());
        $this->assertRegExp("/c06196fbd8/", $this->object_alone->get_thumbnail());
        $this->assertRegExp("/5930279108/", $this->object_alone->get_thumbnail());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_alone->get_thumbnail());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_list->get_thumbnail());
    }

    public function testGet_title()
    {
        $this->assertEquals("un titre", $this->object_list->get_title());
        $this->assertEquals("Australia.gif", $this->object_alone->get_title());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_alone->get_title());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_list->get_title());
    }

    public function testGet_description()
    {
        $this->assertEquals("une description", $this->object_list->get_description());
        $this->assertEquals("drapeau de l'australie", $this->object_alone->get_description());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_alone->get_description());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_list->get_description());
    }

    public function testGet_updated_on()
    {
        $this->assertInstanceOf('DateTime', $this->object_list->get_updated_on());
        $this->assertInstanceOf('DateTime', $this->object_alone->get_updated_on());
        $this->assertEquals(DateTime::createFromFormat('U', '1319126343'), $this->object_list->get_updated_on());
        $this->assertEquals(DateTime::createFromFormat('U', '1314975347'), $this->object_alone->get_updated_on());
    }

    public function testGet_category()
    {
        $this->assertEmpty($this->object_list->get_category());
        $this->assertEmpty($this->object_alone->get_category());
    }

    public function testGet_duration()
    {
        $this->assertEmpty($this->object_list->get_duration());
        $this->assertEmpty($this->object_alone->get_duration());
    }

    public function testGet_view_count()
    {
        $this->assertEquals(1, $this->object_list->get_view_count());
        $this->assertEquals(1, $this->object_alone->get_view_count());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->object_alone->get_view_count());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->object_list->get_view_count());
    }

    public function testGet_rating()
    {
        $this->assertNull($this->object_list->get_rating());
        $this->assertNull($this->object_alone->get_rating());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', $this->object_list->get_created_on());
        $this->assertInstanceOf('DateTime', $this->object_alone->get_created_on());
        $this->assertEquals(DateTime::createFromFormat('U', '1319117962'), $this->object_list->get_created_on());
        $this->assertEquals(DateTime::createFromFormat('U', '1310477820'), $this->object_alone->get_created_on());
    }

    public function testIs_private()
    {
        $this->assertFalse($this->object_list->is_private());
        $this->assertTrue($this->object_alone->is_private());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_BOOL, $this->object_alone->is_private());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_BOOL, $this->object_list->is_private());
    }

    public function testGet_type()
    {
        $this->assertEquals('album', $this->object_list->get_type());
        $this->assertEquals('album', $this->object_alone->get_type());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_alone->get_type());
        $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object_list->get_type());
    }
}
