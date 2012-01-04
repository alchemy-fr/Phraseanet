<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
require_once __DIR__ . '/../Alchemy/Phrasea/Core.php';

use Alchemy\Phrasea\Core;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class bootstrap
{

  protected static $core;

  public static function set_php_configuration()
  {
    return Core::initPHPConf();
  }

  /**
   * 
   * @param $env 
   * @return Alchemy\Phrasea\Core 
   */
  public static function execute($env = null)
  {
    if (static::$core)
    {
      return static::$core;
    }

    static::$core = new Core($env);

    return static::$core;
  }

  /**
   *
   * @return Alchemy\Phrasea\Core 
   */
  public static function getCore()
  {
    return static::$core;
  }

  public static function register_autoloads()
  {
    return Core::initAutoloads();
  }

}
