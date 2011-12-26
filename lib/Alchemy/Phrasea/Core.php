<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Serializer;

require_once __DIR__ . '/../../vendor/Silex/vendor/pimple/lib/Pimple.php';

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Core extends \Pimple
{

  
  protected static $availableLanguages = array(
        'ar_SA' => 'العربية'
        , 'de_DE' => 'Deutsch'
        , 'en_GB' => 'English'
        , 'es_ES' => 'Español'
        , 'fr_FR' => 'Français'
    );
  
  public function __construct($isDev = false)
  {

    /**
     * Autoload
     */
    static::initAutoloads();

    $this['Version'] = $this->share(function()
            {
              return new Core\Version();
            });

    $this['EM'] = $this->share(function()
            {
              $doctrine = new Core\Service\Doctrine();
              return $doctrine->getEntityManager();
            });


    if (\setup::is_installed())
    {
      $this['Registry'] = $this->share(function()
              {
                return \registry::get_instance();
              });
      \phrasea::start();
      $this->enableEvents();
    }
    else
    {

      $this['Registry'] = $this->share(function()
              {
                return new \Setup_Registry();
              });
    }

    /**
     * Initialize Request
     */
    $this['Request'] = $this->share(function()
            {
              return Request::createFromGlobals();
            });

    $this['Serializer'] = $this->share(function()
            {
              $encoders = array(
                  'json' => new Serializer\Encoder\JsonEncoder()
              );

              return new Serializer\Serializer(array(), $encoders);
            });

    self::initPHPConf();

    $this->initLoggers();

    $this->verifyTimeZone();

    $this->detectLanguage();

    $this->enableLocales();


    define('JETON_MAKE_SUBDEF', 0x01);
    define('JETON_WRITE_META_DOC', 0x02);
    define('JETON_WRITE_META_SUBDEF', 0x04);
    define('JETON_WRITE_META', 0x06);

    if (\setup::is_installed())
    {
      $gatekeeper = \gatekeeper::getInstance();
      $gatekeeper->check_directory();
    }
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

  /**
   *
   * @return \Registry 
   */
  public function getRegistry()
  {
    return $this['Registry'];
  }

  /**
   *
   * @return \Doctrine\ORM\EntityManager 
   */
  public function getEntityManager()
  {
    return $this['EM'];
  }

  /**
   *
   * @return Alchemy\Phrasea\Core\Version 
   */
  public function getVersion()
  {
    return $this['Version'];
  }

  /**
   *
   * @return boolean 
   */
  public function isAuthenticated()
  {
    $session = \Session_Handler::getInstance(\appbox::get_instance());

    return $session->is_authenticated();
  }

  /**
   *
   * @return \User_adapter 
   */
  public function getAuthenticatedUser()
  {
    $appbox = \appbox::get_instance();
    $session = \Session_Handler::getInstance($appbox);

    return \User_Adapter::getInstance($session->get_usr_id(), $appbox);
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

  /**
   *
   * @return Array 
   */
  public static function getAvailableLanguages()
  {
    return static::$availableLanguages;
  }
  
  protected function detectLanguage()
  {
    $this->getRequest()->setDefaultLocale(
            $this->getRegistry()->get('GV_default_lng', 'en_GB')
    );

    $cookies = $this->getRequest()->cookies;

    if (isset(static::$availableLanguages[$cookies->get('locale')]))
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

    require_once __DIR__ . '/../../vendor/Twig/lib/Twig/Autoloader.php';
    require_once __DIR__ . '/../../vendor/Twig-extensions/lib/Twig/Extensions/Autoloader.php';

    \Twig_Autoloader::register();
    \Twig_Extensions_Autoloader::register();

    $loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();

    spl_autoload_register(array('Alchemy\Phrasea\Core', 'phraseaAutoload'));

    $loader->registerNamespaces(array(
        'Alchemy' => __DIR__ . '/../..',
        'Symfony\\Component\\Yaml' => __DIR__ . '/../../vendor/symfony/src',
        'Symfony\\Component\\Console' => __DIR__ . '/../../vendor/symfony/src',
        'Symfony\\Component\\Serializer' => __DIR__ . '/../../vendor/symfony/src',
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
