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

require_once __DIR__ . '/../Alchemy/Phrasea/Kernel.php';
    
class bootstrap
{
  
  static $kernel;

  public static function set_php_configuration()
  {
    return Alchemy\Phrasea\Kernel::initPHPConf();
  }

  /**
   *
   * @return Alchemy\Phrasea\Kernel 
   */
  public static function execute()
  {
    if(static::$kernel)
    {
      return static::$kernel;
    }
    
    static::$kernel = new Alchemy\Phrasea\Kernel();
    
    return static::$kernel;
  }

  public static function register_autoloads()
  {
    return Alchemy\Phrasea\Kernel::initAutoloads();
  }

}
