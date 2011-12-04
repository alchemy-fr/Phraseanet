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
class Session_Storage_CommandLine extends Session_Storage_Abstract implements Session_Storage_Interface
{

  /**
   *
   * @var Session_Storage_CommandLine
   */
  protected static $_instance;
  /**
   *
   * @var string
   */
  private static $_name = '';
  /**
   *
   * @var Array
   */
  private static $_cli_storage = array();

  /**
   *
   * @param string $session_name
   * @return Session_Storage_CommandLine
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
   * @param string $name
   * @return Session_Storage_CommandLine
   */
  protected function __construct($name)
  {
    return $this;
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function get($key, $default_value = null)
  {
    return isset(self::$_cli_storage[self::$_name][$key]) ? self::$_cli_storage[self::$_name][$key] : $default_value;
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function has($key)
  {
    return isset(self::$_cli_storage[self::$_name][$key]);
  }

  /**
   *
   * @param string $key
   * @param mixed $value
   * @return boolean
   */
  public function set($key, $value)
  {
    $this->require_open_storage();

    return self::$_cli_storage[self::$_name][$key] = $value;
  }

  /**
   *
   * @param string $key
   * @return boolean
   */
  public function remove($key)
  {
    $retval = null;
    $this->require_open_storage();

    if (isset(self::$_cli_storage[self::$_name][$key]))
    {
      $retval = self::$_cli_storage[self::$_name][$key];
      unset(self::$_cli_storage[self::$_name][$key]);
    }

    return $retval;
  }

  /**
   * Return PHP session name
   *
   * @return string
   */
  public function getName()
  {
    return 'commandLine';
  }

  /**
   * Return PHP session Id
   *
   * @return string
   */
  public function getId()
  {
    return 'commandLine';
  }


  public function reset()
  {
    self::$_cli_storage[self::$_name] = array();

    return;
  }

  /**
   *
   * @return Void
   */
  public function destroy()
  {
    unset(self::$_cli_storage[self::$_name]);

    return;
  }

}
