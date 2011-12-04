<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     Session
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Session_Authentication_Token implements Session_Authentication_Interface
{

  /**
   *
   * @var appbox
   */
  protected $appbox;
  /**
   *
   * @var string
   */
  protected $token;
  protected $user;

  /**
   *
   * @param appbox $appbox
   * @param type $token
   * @return Session_Authentication_Token
   */
  public function __construct(appbox &$appbox, $token)
  {
    $this->appbox = $appbox;
    $this->token = $token;

    try
    {
      $datas = random::helloToken($token);
      $usr_id = $datas['usr_id'];
      $this->user = User_Adapter::getInstance($usr_id, $this->appbox);
    }
    catch (Exception_NotFound $e)
    {
      throw new Exception_Session_WrongToken();
    }

    return $this;
  }

  /**
   *
   * @return Session_Authentication_Token
   */
  public function prelog()
  {
    return $this;
  }

  /**
   *
   * @return User_Adapter
   */
  public function signOn()
  {

    return $this->user;
  }
  /**
   *
   * @return User_Adapter
   */
  public function get_user()
  {

    return $this->user;
  }

  /**
   *
   * @return Session_Authentication_Token
   */
  public function postlog()
  {
    return $this;
  }

}
