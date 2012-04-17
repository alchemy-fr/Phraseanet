<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class Session_Authentication_NativeTest extends PhraseanetPHPUnitAbstract
{

  /**
   * @var Session_Authentication_Native
   */
  protected $object;
  protected $local_user;

  public function setUp()
  {
    $login = 'testlogin';
    $password = 'testpassword';
    $usr_id = User_Adapter::get_usr_id_from_login($login);
    if ($usr_id)
    {
      $this->local_user = User_Adapter::getInstance($usr_id, appbox::get_instance(\bootstrap::getCore()));
    }
    else
    {
      $this->local_user = User_Adapter::create(appbox::get_instance(\bootstrap::getCore()), $login, $password, null, false);
    }
    $this->object = new Session_Authentication_Native(appbox::get_instance(\bootstrap::getCore()), $login, $password);
  }

  public function tearDown()
  {
    $this->local_user->delete();
  }

  public function testSet_captcha_challenge()
  {
    $this->object->set_captcha_challenge(false);
  }


  public function testSignOn()
  {
    $user = $this->object->signOn();
    $this->assertInstanceOf('User_Adapter', $user);
  }


}
