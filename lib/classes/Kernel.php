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
 * Access Control List class
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */


namespace Phrasea;


require_once __DIR__ . '/../vendor/Silex/vendor/pimple/lib/Pimple.php';
require_once __DIR__ . '/../vendor/Twig/lib/Twig/Autoloader.php';
require_once __DIR__ . '/../vendor/Twig-extensions/lib/Twig/Extensions/Autoloader.php';
require_once __DIR__ . '/../vendor/symfony/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';


class Kernel extends \Pimple
{

  public function __construct()
  {
    //autoload
    $this->bootstrap();
    
    /**
     * Load entity manager
     */
    $this['EM'] = $this->share(function()
            {
              return static::getEntityManager();
            });
    /**
     * Load registry
     */
    $this['registry'] = $this->share(function()
            {
              return new \registry(new \cache_opcode_adapter(crc32(__DIR__)));
            });


    /**
     * load Gatekeeper
     */
    $this['gatekeeper'] = $this->share(function()
            {
              return new \gatekeeper();
            });


    /**
     * Load Gatekeeper
     */
    $this['dispatcher'] = $this->share(function ()
            {
              $dispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
            });

    /**
     * Initialize Request
     */
    $this['request'] = $this->share(function()
            {
              $app['request'] = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
            });

  }

  protected function phrasea_autoload($class_name)
  {
     if (file_exists(__CUSTOMDIR__ . '/classes/'
                    . str_replace('_', '/', $class_name) . '.class.php'))
    {
      require_once __CUSTOMDIR__ . '/classes/'
              . str_replace('_', '/', $class_name) . '.class.php';
    }
    elseif (file_exists(__LIBDIR__ . '/classes/'
                    . str_replace('_', '/', $class_name) . '.class.php'))
    {
      require_once __LIBDIR__ . '/classes/'
              . str_replace('_', '/', $class_name) . '.class.php';
    }
  }

  
  protected function bootstrap()
  {
    $this->define_dirs();
    $loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
    spl_autoload_register(array($this, 'phrasea_autoload'));
    \Twig_Autoloader::register();
    \Twig_Extensions_Autoloader::register();
    require_once __DIR__ . '/../vendor/Silex/autoload.php';
    $loader->registerNamespaces(array(
        'Symfony\\Component\\Yaml' => __LIBDIR__ . '/vendor/symfony/src',
        'Symfony\\Component\\Console' => __LIBDIR__ . '/vendor/symfony/src',
        'Phrasea' => __LIBDIR__ . '/classes',
    ));
    
  }
 
  private function define_dirs()
  {
    $root = dirname(dirname(dirname(__FILE__)));
    if (!defined('__LIBDIR__'))
      define('__LIBDIR__', $root . '/lib');
    if (!defined('__CUSTOMDIR__'))
      define('__CUSTOMDIR__', $root . '/config');
  }

}

?>
