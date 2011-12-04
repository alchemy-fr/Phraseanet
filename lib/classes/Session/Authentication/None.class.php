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
class Session_Authentication_None implements Session_Authentication_Interface
{

  /**
   *
   * @var User_Adapter
   */
  protected $user;

  /**
   *
   * @param User_Adapter $user
   * @return Session_Authentication_None
   */
  public function __construct(User_Adapter $user)
  {
    $this->user = $user;

    return $this;
  }

  /**
   *
   * @return Session_Authentication_None
   */
  public function prelog()
  {
    return $this;
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
   * @return User_Adapter
   */
  public function signOn()
  {
    return $this->user;
  }

  /**
   *
   * @return Session_Authentication_None
   */
  public function postlog()
  {
    return $this;
  }

}
