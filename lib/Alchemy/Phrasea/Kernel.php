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
 * Kernel
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

namespace Alchemy\Phrasea;

use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../../vendor/Silex/vendor/pimple/lib/Pimple.php';

class Kernel extends \Pimple
{

  public function __construct($isDev = false)
  {

    /**
     * Autoload
     */
    static::initAutoloads();

    $this['Version'] = $this->share(function()
            {
              return new Kernel\Version();
            });

    $this['EM'] = $this->share(function()
            {
              $doctrine = new Kernel\Service\Doctrine();
              return $doctrine->getEntityManager();
            });


    $this['Registry'] = $this->share(function()
            {
              return \registry::get_instance();
            });

    /**
     * Initialize Request
     */
    $this['Request'] = $this->share(function()
            {
              return Request::createFromGlobals();
            });

    self::initPHPConf();
    
    $this->initLoggers();
            
    $this->verifyTimeZone();

    \phrasea::start();

    $this->detectLanguage();

    $this->enableLocales();

    $this->enableEvents();

    define('JETON_MAKE_SUBDEF', 0x01);
    define('JETON_WRITE_META_DOC', 0x02);
    define('JETON_WRITE_META_SUBDEF', 0x04);
    define('JETON_WRITE_META', 0x06);

    $gatekeeper = \gatekeeper::getInstance();
    $gatekeeper->check_directory();

    return;
  }

  /**
   *
   * @return Request 
   */
  public function getRequest()
  {
    return $this['Request'];
  }

  public function getRegistry()
  {
    return $this['Registry'];
  }

  /**
   *
   * @return Alchemy\Phrasea\Kernel\Version 
   */
  public function getVersion()
  {
    return $this['Version'];
  }

  protected function verifyTimezone()
  {
    if ($this->getRegistry()->is_set('GV_timezone'))
      date_default_timezone_set($this->getRegistry()->get('GV_timezone'));
    else
      date_default_timezone_set('Europe/Berlin');

    return;
  }

  protected function enableLocales()
  {
    mb_internal_encoding("UTF-8");
    \phrasea::use_i18n($this->getRequest()->getLocale());

    return;
  }

  protected function enableEvents()
  {
    \phrasea::load_events();

    return;
  }

  protected function initLoggers()
  {
    $php_log = $this->getRegistry()->get('GV_RootPath') . 'logs/php_error.log';
    
    ini_set('error_log', $php_log);

    if ($this->getRegistry()->get('GV_debug'))
    {
      ini_set('display_errors', 'on');
      ini_set('display_startup_errors', 'on');
    }
    else
    {
      ini_set('display_errors', 'off');
      ini_set('display_startup_errors', 'off');
    }

    if ($this->getRegistry()->get('GV_log_errors'))
    {
      ini_set('log_errors', 'on');
    }
    else
    {
      ini_set('log_errors', 'off');
    }
    
    return $this;
  }
  
  protected function detectLanguage()
  {
    $availables = array(
        'ar_SA' => 'العربية'
        , 'de_DE' => 'Deutsch'
        , 'en_GB' => 'English'
        , 'es_ES' => 'Español'
        , 'fr_FR' => 'Français'
    );

    $this->getRequest()->setDefaultLocale(
            $this->getRegistry()->get('GV_default_lng', 'en_GB')
    );

    $cookies = $this->getRequest()->cookies;

    if (isset($availables[$cookies->get('locale')]))
    {
      $this->getRequest()->setLocale($cookies->get('locale'));
    }

    return;
  }

  protected static function phraseaAutoload($class_name)
  {
    if (file_exists(__DIR__ . '/../../../config/classes/'
                    . str_replace('_', '/', $class_name) . '.class.php'))
    {
      require_once __DIR__ . '/../../../config/classes/'
              . str_replace('_', '/', $class_name) . '.class.php';
    }
    elseif (file_exists(__DIR__ . '/../../classes/'
                    . str_replace('_', '/', $class_name) . '.class.php'))
    {
      require_once __DIR__ . '/../../classes/'
              . str_replace('_', '/', $class_name) . '.class.php';
    }

    return;
  }

  public static function initAutoloads()
  {
    require_once __DIR__ . '/../../vendor/symfony/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';
    require_once __DIR__ . '/../../vendor/symfony/src/Symfony/Component/ClassLoader/ApcUniversalClassLoader.php';

    require_once __DIR__ . '/../../vendor/Twig/lib/Twig/Autoloader.php';
    require_once __DIR__ . '/../../vendor/Twig-extensions/lib/Twig/Extensions/Autoloader.php';

    \Twig_Autoloader::register();
    \Twig_Extensions_Autoloader::register();

    $loader = new \Symfony\Component\ClassLoader\UniversalClassLoader(crc32(__DIR__));

    spl_autoload_register(array('Alchemy\Phrasea\Kernel', 'phraseaAutoload'));

    $loader->registerNamespaces(array(
        'Alchemy' => __DIR__ . '/../..',
        'Symfony\\Component\\Yaml' => __DIR__ . '/../../vendor/symfony/src',
        'Symfony\\Component\\Console' => __DIR__ . '/../../vendor/symfony/src',
    ));

    $loader->register();
    
    require_once __DIR__ . '/../../vendor/Silex/autoload.php';

    return;
  }
  
  public static function initPHPConf()
  {
    ini_set('output_buffering', '4096');
    if ((int) ini_get('memory_limit') < 2048)
      ini_set('memory_limit', '2048M');
    ini_set('error_reporting', '6143');
    ini_set('default_charset', 'UTF-8');
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.auto_start', '0');
    ini_set('session.hash_function', '1');
    ini_set('session.hash_bits_per_character', '6');
    ini_set('allow_url_fopen', 'on');
    
    return;
  }

}
