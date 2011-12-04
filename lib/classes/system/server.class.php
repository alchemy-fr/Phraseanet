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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class system_server
{

  /**
   *
   * @var string
   */
  private $_server_software;

  /**
   *
   * @return system_server
   */
  function __construct()
  {
    $this->_server_software = isset($_SERVER['SERVER_SOFTWARE']) ?
            strtolower($_SERVER['SERVER_SOFTWARE']) : "";

    return $this;
  }

  /**
   * Return true if server is Nginx
   *
   * @return boolean
   */
  public function is_nginx()
  {
    if (strpos($this->_server_software, 'nginx') !== false)

      return true;
    return false;
  }

  /**
   * Return true if server is lighttpd
   *
   * @return boolean
   */
  public function is_lighttpd()
  {
    if (strpos($this->_server_software, 'lighttpd') !== false)

      return true;
    return false;
  }

  /**
   * Return true if server is Apache
   *
   * @return boolean
   */
  public function is_apache()
  {
    if (strpos($this->_server_software, 'apache') !== false)

      return true;
    return false;
  }

  /**
   * Return server platform name
   *
   * @staticvar string $_system
   * @return string
   */
  public static function get_platform()
  {
    static $_system = NULL;
    if ($_system === NULL)
    {
      $_system = strtoupper(php_uname('s'));
      if ($_system == 'WINDOWS NT')
        $_system = 'WINDOWS';
    }

    return($_system);
  }

}
