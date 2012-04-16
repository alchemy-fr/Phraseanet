<?php
require_once __DIR__ . '/../../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../../Bridge_datas.inc';
require_once __DIR__ . '/../../../../lib/classes/Bridge/Api/Dailymotion/Container.class.php';

class Bridge_Api_Dailymotion_ContainerTest extends PHPUnit_Framework_TestCase
{

  /**
   * @var Bridge_Api_Dailymotion_Container
   */
  protected $object;

  protected function setUp()
  {
    $this->test = array(
        'id' => '01234567'
        ,'description' => 'one description'
        , 'name' => 'hello container'
    );
  }

  /**
   * @todo Implement testGet_created_on().
   */
  public function testGet_created_on()
  {
    $this->object = new Bridge_Api_Dailymotion_Container($this->test, 'playlist', 'thumb', 'url');
    $this->assertNull($this->object->get_created_on());
  }

  /**
   * @todo Implement testGet_description().
   */
  public function testGet_description()
  {
    $this->object = new Bridge_Api_Dailymotion_Container($this->test, 'playlist', 'thumb', 'url');
    $this->assertEquals($this->test['description'], $this->object->get_description());
    unset($this->test["description"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_description());
    $this->assertEmpty($this->object->get_description());
  }

  /**
   * @todo Implement testGet_id().
   */
  public function testGet_id()
  {
    $this->object = new Bridge_Api_Dailymotion_Container($this->test, 'playlist', 'thumb', 'url');
    $this->assertEquals($this->test['id'], $this->object->get_id());
    unset($this->test["id"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_id());
    $this->assertEmpty($this->object->get_id());
  }

  /**
   * @todo Implement testGet_thumbnail().
   */
  public function testGet_thumbnail()
  {
    $this->object = new Bridge_Api_Dailymotion_Container($this->test, 'playlist', 'thumb', 'url');
    $this->assertEquals('thumb', $this->object->get_thumbnail());
  }

  /**
   * @todo Implement testGet_title().
   */
  public function testGet_title()
  {
    $this->object = new Bridge_Api_Dailymotion_Container($this->test, 'playlist', 'thumb', 'url');
    $this->assertEquals($this->test['name'], $this->object->get_title());
    unset($this->test["name"]);
    $this->object = new Bridge_Api_Dailymotion_Element($this->test, 'blabla');
    $this->assertInternalType(PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $this->object->get_title());
    $this->assertEmpty($this->object->get_title());
  }

  /**
   * @todo Implement testGet_type().
   */
  public function testGet_type()
  {
    $this->object = new Bridge_Api_Dailymotion_Container($this->test, 'playlist', 'thumb', 'url');
    $this->assertEquals('playlist', $this->object->get_type());
  }

  /**
   * @todo Implement testGet_updated_on().
   */
  public function testGet_updated_on()
  {
    $this->object = new Bridge_Api_Dailymotion_Container($this->test, 'playlist', 'thumb', 'url');
    $this->assertNull($this->object->get_updated_on());
  }

  /**
   * @todo Implement testGet_url().
   */
  public function testGet_url()
  {
    $this->object = new Bridge_Api_Dailymotion_Container($this->test, 'playlist', 'thumb', 'url');
    $this->assertEquals('url', $this->object->get_url());
  }

}
