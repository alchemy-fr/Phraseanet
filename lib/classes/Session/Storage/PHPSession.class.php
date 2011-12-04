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
class Session_Storage_PHPSession extends Session_Storage_Abstract implements Session_Storage_Interface
{

  /**
   *
   * @var Session_Storage_PHPSession
   */
  protected static $_instance;
  /**
   *
   * @var string
   */
  protected $name = 'PHPSESSID';

  /**
   *
   * @param string $session_name
   * @return Session_Storage_PHPSession
   */
  public static function getInstance($session_name)
  {
    if (!self::$_instance)
    {
      self::$_instance = new self($session_name);
    }

    return self::$_instance;
  }

  /**
   *
   * @param string $session_name
   * @return Session_Storage_PHPSession
   */
  protected function __construct($session_name)
  {
    $this->name = $session_name;
    $this->start();

    return $this;
  }

  /**
   *
   * @return Session_Storage_PHPSession
   */
  protected function start()
  {
    session_name($this->name);
    session_start();
    $this->open = true;

    return $this;
  }

  /**
   *
   * @return Session_Storage_PHPSession
   */
  public function close()
  {
    if ($this->open)
    {
      session_write_close();
    }
    parent::close();

    return $this;
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function has($key)
  {
    return isset($_SESSION[$key]);
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function get($key, $default_value = null)
  {
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default_value;
  }

  public function set($key, $value)
  {
    $this->require_open_storage();
    $_SESSION[$key] = $value;

    return $this;
  }

  public function remove($key)
  {
    $this->require_open_storage();
    if (isset($_SESSION[$key]))
      unset($_SESSION[$key]);

    return $this;
  }

  /**
   * Return PHP session name
   *
   * @return string
   */
  function getName()
  {
    return session_name();
  }

  /**
   * Return PHP session Id
   *
   * @return <type>
   */
  function getId()
  {
    return session_id();
  }

  public function reset()
  {
    $_SESSION = array();

    return $this;
  }

  /**
   *
   * @return Void
   */
  public function destroy()
  {
    session_destroy();
    $this->open = false;

    return;
  }

}
