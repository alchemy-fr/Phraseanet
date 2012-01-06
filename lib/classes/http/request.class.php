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
class http_request
{
  /**
   * @todo enable filters
   */
//  private static $_FILTER_IMPLEMENTED = extension_loaded;
//
//  const VALIDATE_BOOLEAN        = FILTER_VALIDATE_BOOLEAN;
//  const VALIDATE_EMAIL          = FILTER_VALIDATE_EMAIL;
//  const VALIDATE_FLOAT          = FILTER_VALIDATE_FLOAT;
//  const VALIDATE_INT            = FILTER_VALIDATE_INT;
//  const VALIDATE_IP             = FILTER_VALIDATE_IP;
//  const VALIDATE_REGEXP         = FILTER_VALIDATE_REGEXP;
//  const VALIDATE_URL            = FILTER_VALIDATE_URL;
//
//  const SANITIZE_EMAIL          = FILTER_SANITIZE_EMAIL;
//  const SANITIZE_ENCODED        = FILTER_SANITIZE_ENCODED;
//  const SANITIZE_MAGIC_QUOTES   = FILTER_SANITIZE_MAGIC_QUOTES;
//  const SANITIZE_NUMBER_FLOAT   = FILTER_SANITIZE_NUMBER_FLOAT;
  const SANITIZE_NUMBER_INT = 'int';
//  const SANITIZE_SPECIAL_CHARS  = FILTER_SANITIZE_SPECIAL_CHARS;
  const SANITIZE_STRING = 'string';
//  const SANITIZE_STRIPPED       = FILTER_SANITIZE_STRIPPED;
//  const SANITIZE_URL            = FILTER_SANITIZE_URL;

  /**
   *
   * @var <type>
   */
  private static $_instance;

  /**
   *
   * @var boolean
   */
  protected static $_cli_usage;

  /**
   *
   * @var <type>
   */
  protected $code;

  /**
   *
   * @return http_request
   */
  public static function getInstance()
  {
    if (!(self::$_instance instanceof self))
    {
      self::$_instance = new self();
    }

    return self::$_instance;
  }

  /**
   *
   * @return http_request
   */
  function __construct()
  {
    return $this;
  }

  /**
   *
   * @return boolean
   */
  public function is_ajax()
  {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')

      return true;
    return false;
  }
  
  public function is_secure()
  {
    return (
        isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == 1)
    );
  }

  public function comes_from_flash()
  {
    return (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/\bflash\b/i', $_SERVER['HTTP_USER_AGENT']) > 0);
  }

  /**
   *
   * @return int
   */
  public function get_code()
  {
    if (is_null($this->code) && isset($_SERVER['REDIRECT_STATUS']))
    {
      $this->code = $_SERVER['REDIRECT_STATUS'];
    }

    return $this->code;
  }

  /**
   *
   * @param int $code
   * @return http_request
   */
  public function set_code($code)
  {
    $this->code = (int) $code;

    return $this;
  }

  /**
   *
   * @return Array
   */
  public function get_parms()
  {
    $parm = array();
    $nargs = func_num_args();

    if ($nargs == 1 && is_array(func_get_arg(0)))
    {
      foreach (func_get_arg(0) as $key => $nom)
      {
        if (is_string($key))
        {
          $value = isset($_GET[$key]) ?
                  $_GET[$key] : (isset($_POST[$key]) ? $_POST[$key] : NULL);
          switch ($nom)
          {
            case self::SANITIZE_NUMBER_INT:
              $value = (int) $value;
              break;
            case self::SANITIZE_STRING:
              $value = trim((string) $value);
              break;
            default:
              break;
          }
          $parm[$key] = $value;
        }
        else
        {
          $parm[$nom] = isset($_GET[$nom]) ?
                  $_GET[$nom] : (isset($_POST[$nom]) ? $_POST[$nom] : NULL);
        }
      }
    }
    else
    {
      for ($i = 0; $i < $nargs; $i++)
      {
        $nom = func_get_arg($i);
        $parm[$nom] = isset($_GET[$nom]) ?
                $_GET[$nom] : (isset($_POST[$nom]) ? $_POST[$nom] : NULL);
      }
    }

    return($parm);
  }

  /**
   *
   * @param array $indexes
   * @param string $serializeds_datas_index
   * @return array
   */
  public function get_parms_from_serialized_datas(Array $indexes, $serializeds_datas_index)
  {
    $parm = array();
    $tmp_parms = array();

    if (isset($_GET[$serializeds_datas_index]))
      parse_str($_GET[$serializeds_datas_index], $tmp_parms);
    elseif (isset($_POST[$serializeds_datas_index]))
      parse_str($_POST[$serializeds_datas_index], $tmp_parms);

    if (count($tmp_parms) > 0)
    {
      foreach ($indexes as $nom)
      {
        $parm[$nom] = isset($tmp_parms[$nom]) ? $tmp_parms[$nom] : NULL;
      }
    }

    return $parm;
  }

  /**
   *
   * @return boolean
   */
  public function has_post_datas()
  {
    return!empty($_POST);
  }

  /**
   *
   * @return Array
   */
  public function get_post_datas()
  {
    return $_POST;
  }

  /**
   *
   * @return boolean
   */
  public function has_get_datas()
  {
    return!empty($_GET);
  }

  /**
   *
   * @return boolean
   */
  public function has_datas()
  {
    return ($this->has_post_datas() || $this->has_get_datas());
  }

  /**
   *
   * @param mixed content $data
   * @param const $filter
   * @return mixed content
   */
  public function filter($data, $filter)
  {
    return filter_var($data, $filter);
  }

  /**
   * Tells wheter or not it's command line script
   *
   * @return boolean
   */
  public static function is_command_line()
  {
    if (self::$_cli_usage === null)
    {
      $sapi_name = strtolower(substr(php_sapi_name(), 0, 3));
      self::$_cli_usage = ($sapi_name == 'cli');
    }

    return self::$_cli_usage;
  }

}
