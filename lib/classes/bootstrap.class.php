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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . '/../Alchemy/Phrasea/Core.php';
    
class bootstrap
{
  
  protected static $core;

  public static function set_php_configuration()
  {
    return Alchemy\Phrasea\Core::initPHPConf();
  }

  /**
   *
   * @return Alchemy\Phrasea\Core 
   */
  public static function execute($env = 'main')
  {
    if(static::$core)
    {
      return static::$core;
    }
    
    static::$core = new Alchemy\Phrasea\Core($env);
    
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
    return Alchemy\Phrasea\Core::initAutoloads();
  }

}
