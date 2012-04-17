<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../../Bridge_datas.inc';
require_once __DIR__ . '/../../../../lib/classes/Bridge/Api/Dailymotion/Element.class.php';

class Bridge_Api_Dailymotion_ElementTest extends PHPUnit_Framework_TestCase
{

  /**
   * @var Bridge_Api_Dailymotion_Element
   */
  protected $object;
  protected $test;

  public function setUp()
  {
    $this->test = array(
        'created_time' => time()
        , 'description' => 'Description of a dailymotion element'
        , 'id' => "1"
        , 'thumbnail_medium_url' => 'thumbnail_medium_url'
        , 'title' => 'title of dailymotion lement'
        , 'modified_time' => time()
        , 'url' => 'www.my.element/url'
        , 'private' => 1
        , 'views_total' => '34'
        , 'ratings_total' => '4'
        , 'duration' => 80
        , 'channel' => 'animation'
    );
  }

  public function testGet_created_on()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertEquals(DateTime::createFromFormat('U', $this->test['created_time']), $this->object->get_created_on());
  }

  public function testGet_description()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertEquals($this->test['description'], $this->object->get_description());
    unset($this->test["description"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_description());
    $this->assertEmpty($this->object->get_description());
  }

  public function testGet_id()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertEquals($this->test['id'], $this->object->get_id());
    unset($this->test["id"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_description());
    $this->assertEmpty($this->object->get_id());
  }

  public function testGet_thumbnail()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertEquals($this->test['thumbnail_medium_url'], $this->object->get_thumbnail());
    unset($this->test["thumbnail_medium_url"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_thumbnail());
    $this->assertEmpty($this->object->get_thumbnail());
  }

  public function testGet_title()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertEquals($this->test['title'], $this->object->get_title());
    unset($this->test["title"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_title());
    $this->assertEmpty($this->object->get_title());
  }

  public function testGet_type()
  {
    $type = 'kikoo';
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, $type);
    $this->assertEquals($type, $this->object->get_type());
    $type = 'kooki';
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, $type);
    $this->assertEquals($type, $this->object->get_type());
  }

  public function testGet_updated_on()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertEquals(DateTime::createFromFormat('U', $this->test['modified_time']), $this->object->get_updated_on());
  }

  public function testGet_url()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertEquals($this->test['url'], $this->object->get_url());
    unset($this->test["url"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_url());
    $this->assertEmpty($this->object->get_url());
  }

  public function testIs_private()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_BOOL, $this->object->is_private());
    $this->assertTrue($this->object->is_private());
    unset($this->test["private"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_BOOL, $this->object->is_private());
    $this->assertFalse($this->object->is_private());
  }

  public function testGet_duration()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_duration());
    $this->assertEquals("01:20", $this->object->get_duration());
    unset($this->test["duration"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_duration());
    $this->assertEquals("00:00", $this->object->get_duration());
  }

  public function testGet_view_count()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->object->get_view_count());
    $this->assertEquals($this->test['views_total'], $this->object->get_view_count());
    unset($this->test["views_total"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->object->get_view_count());
    $this->assertEquals(0 , $this->object->get_view_count());
  }

  public function testGet_rating()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->object->get_rating());
    $this->assertEquals($this->test['ratings_total'], $this->object->get_rating());
    unset($this->test["ratings_total"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_INT, $this->object->get_rating());
    $this->assertEquals(0 , $this->object->get_rating());
  }

  public function testGet_category()
  {
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertEquals($this->test['channel'], $this->object->get_category());
    unset($this->test["channel"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_category());
    $this->assertEmpty($this->object->get_category());
  }

}
