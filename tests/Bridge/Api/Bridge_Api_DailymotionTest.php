<?php
require_once __DIR__ . '/../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../Bridge_datas.inc';
require_once __DIR__ . '/../../../lib/classes/Bridge/Api/Dailymotion.class.php';

class Bridge_Api_DailymotionTest extends PHPUnit_Framework_TestCase
{

  /**
   * @var Bridge_Api_Dailymotion
   */
  protected $object;

  protected function setUp()
  {
    $this->object = $this->getMock("Bridge_Api_Dailymotion", array("initialize_transport", "set_auth_params"), array(registry::get_instance(), $this->getMock("Bridge_Api_Auth_Interface")));

  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown()
  {

  }

  /**
   * @todo Implement testConnect().
   */
  public function testConnect()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testReconnect().
   */
  public function testReconnect()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_user_id().
   */
  public function testGet_user_id()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_user_name().
   */
  public function testGet_user_name()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_name().
   */
  public function testGet_name()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_icon_url().
   */
  public function testGet_icon_url()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_image_url().
   */
  public function testGet_image_url()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_url().
   */
  public function testGet_url()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_infos().
   */
  public function testGet_infos()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_default_element_type().
   */
  public function testGet_default_element_type()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_default_container_type().
   */
  public function testGet_default_container_type()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_element_types().
   */
  public function testGet_element_types()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_container_types().
   */
  public function testGet_container_types()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_object_class_from_type().
   */
  public function testGet_object_class_from_type()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testList_elements().
   */
  public function testList_elements()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testList_containers().
   */
  public function testList_containers()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testUpdate_element().
   */
  public function testUpdate_element()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testCreate_container().
   */
  public function testCreate_container()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testAdd_element_to_container().
   */
  public function testAdd_element_to_container()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testDelete_object().
   */
  public function testDelete_object()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testAcceptable_records().
   */
  public function testAcceptable_records()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_element_status().
   */
  public function testGet_element_status()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testMap_connector_to_element_status().
   */
  public function testMap_connector_to_element_status()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_error_message_from_status().
   */
  public function testGet_error_message_from_status()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testHandle_exception().
   */
  public function testHandle_exception()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testUpload().
   */
  public function testUpload()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_element_from_id().
   */
  public function testGet_element_from_id()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_container_from_id().
   */
  public function testGet_container_from_id()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_category_list().
   */
  public function testGet_category_list()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

}
