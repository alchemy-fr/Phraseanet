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
 * @package     cache
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class cache_opcode_adapter implements cache_opcode_Interface
{
  /**
   *
   */
  const APC = 0;
  /**
   *
   */
  const XCACHE = 1;
  /**
   *
   */
  const NOCACHE = 2;

  /**
   *
   * @var const
   */
  protected $cache_method;
  /**
   *
   * @var array
   */
  protected static $_static_cache = array();
  /**
   *
   * @var string
   */
  protected $prefix;

  /**
   *
   * @return cache_opcode_adapter
   */
  public function __construct($prefix = '')
  {
    if (!http_request::is_command_line() && function_exists('apc_store'))
      $this->cache_method = self::APC;
    elseif (!http_request::is_command_line() && function_exists('xcache_set'))
      $this->cache_method = self::XCACHE;
    else
      $this->cache_method = self::NOCACHE;

    $this->prefix = $prefix;

    return $this;
  }

  /**
   *
   * @param string $key
   * @return mixed
   */
  public function get($key)
  {
    $key = sprintf('%s_%s', $this->prefix, $key);
    switch ($this->cache_method)
    {
      case self::APC:
        return apc_fetch($key);
        break;
      case self::XCACHE:
        return xcache_get($key);
        break;
      default:
        return isset(self::$_static_cache[$key]) ? self::$_static_cache[$key] : null;
        break;
    }
  }

  /**
   *
   * @param string $key
   * @param mixed $var
   * @return cache_opcode_adapter
   */
  public function set($key, $var)
  {
    $key = sprintf('%s_%s', $this->prefix, $key);
    switch ($this->cache_method)
    {
      case self::APC:
        if ($this->is_set($key))
          apc_delete($key);
        apc_store($key, $var);
        break;
      case self::XCACHE:
        if ($this->is_set($key))
          $this->un_set ($key);
        xcache_set($key, $var);
        break;
      default:
        self::$_static_cache[$key] = $var;
        break;
    }

    return $this;
  }

  /**
   *
   * @param string $key
   * @return boolean
   */
  public function is_set($key)
  {
    $key = sprintf('%s_%s', $this->prefix, $key);
    switch ($this->cache_method)
    {
      case self::APC:
        if (function_exists('apc_exists'))
        {
          return apc_exists($key);
        }
        else
        {
          apc_fetch($key, $succes);

          return $succes;
        }
        break;
      case self::XCACHE:
        return xcache_isset($key);
        break;
      default:
        return isset(self::$_static_cache[$key]);
        break;
    }
  }

  /**
   *
   * @param string $key
   * @return cache_opcode_adapter
   */
  public function un_set($key)
  {
    $key = sprintf('%s_%s', $this->prefix, $key);
    switch ($this->cache_method)
    {
      case self::APC:
        apc_delete($key);
        break;
      case self::XCACHE:
        xcache_unset($key);
        break;
      default:
        if (isset(self::$_static_cache[$key]))
          unset(self::$_static_cache[$key]);
        break;
    }

    return $this;
  }

}
