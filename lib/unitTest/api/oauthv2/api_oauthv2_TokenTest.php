<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class API_OAuth2_TokenTest extends PhraseanetPHPUnitAbstract
{

  /**
   * @var API_OAuth2_Application
   */
  protected $application;

  /**
   * @var API_OAuth2_Token
   */
  protected $object;

  public function setUp()
  {
    $appbox = appbox::get_instance();
    $this->application = API_OAuth2_Application::create($appbox, self::$user, 'test app');
    $account = API_OAuth2_Account::load_with_user($appbox, $this->application, self::$user);

    try
    {
      new API_OAuth2_Token($appbox, $account);
      $this->fail();
    }
    catch (Exception $e)
    {

    }

    $this->object = API_OAuth2_Token::create($appbox, $account);
  }

  public function tearDown()
  {
    $this->application->delete();
  }

  protected function assertmd5($md5)
  {
    $this->assertTrue((count(preg_match('/[a-z0-9]{32}/', $md5)) === 1));
  }

  public function testGet_value()
  {
    $this->assertmd5($this->object->get_value());
  }

  public function testSet_value()
  {
    $value = md5('prout');
    $this->object->set_value($value);
    $this->assertEquals($value, $this->object->get_value());
  }

  public function testGet_session_id()
  {
    $this->assertNull($this->object->get_session_id());
  }

  public function testSet_session_id()
  {
    $this->object->set_session_id(458);
    $this->assertEquals(458, $this->object->get_session_id());
  }

  public function testGet_expires()
  {
    $this->assertInternalType('string', $this->object->get_expires());
  }

  public function testSet_expires()
  {
    $date = time() + 7200;
    $this->object->set_expires($date);
    $this->assertEquals($date, $this->object->get_expires());
  }

  public function testGet_scope()
  {
    $this->assertNull($this->object->get_scope());
  }

  public function testset_scope()
  {
    $this->assertNull($this->object->get_scope());
    $scope = "prout";
    $this->object->set_scope($scope);
    $this->assertEquals($scope, $this->object->get_scope());
  }

  public function testGet_account()
  {
    $this->assertInstanceOf('API_OAuth2_Account', $this->object->get_account());
  }

  public function testRenew()
  {
    $first = $this->object->get_value();
    $this->assertMd5($first);
    $this->object->renew();
    $second = $this->object->get_value();
    $this->assertMd5($second);
    $this->assertNotEquals($second, $first);
  }

  public function testLoad_by_oauth_token()
  {
    $token = $this->object->get_value();
    $loaded = API_OAuth2_Token::load_by_oauth_token(appbox::get_instance(), $token);
    $this->assertInstanceOf('API_OAuth2_Token', $loaded);
    $this->assertEquals($this->object, $loaded);
  }

  public function testGenerate_token()
  {
    for ($i = 0; $i < 100; $i++)
    {
      $this->assertMd5(API_OAuth2_Token::generate_token());
    }
  }

}

