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
use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../vendor/Silex/vendor/pimple/lib/Pimple.php';

/**
 * 
 * Phraseanet Core Container
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

  /**
   *
   * @var Core\Configuration
   */
  private $configuration;

  public function __construct($environement = null)
  {

    /**
     * Autoload
     */
    static::initAutoloads();

    $handler = new Core\Configuration\Handler(
                    new Core\Configuration\Application(),
                    new Core\Configuration\Parser\Yaml()
    );

    $this->configuration = new Core\Configuration($handler, $environement);

    $this->init();

    /**
     * Set version
     */
    $this['Version'] = $this->share(function()
            {
              return new Core\Version();
            });

    if ($this->configuration->isInstalled())
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

    $core = $this;
    /**
     * Set Entity Manager using configuration
     */
    $this['EM'] = $this->share(function() use ($core)
            {
              $serviceName = $core->getConfiguration()->getOrm();
              $configuration = $core->getConfiguration()->getService($serviceName);

              $serviceBuilder = new Core\ServiceBuilder\Orm(
                              $serviceName
                              , $configuration
                              , array("registry" => $core["Registry"])
              );

              return $serviceBuilder->buildService()->getService();
            });



    $this["Twig"] = $this->share(function() use ($core)
            {
              $serviceName = $core->getConfiguration()->getTemplating();

              $configuration = $core->getConfiguration()->getService($serviceName);

              $serviceBuilder = new Core\ServiceBuilder\TemplateEngine(
                              $serviceName, $configuration
              );

              return $serviceBuilder->buildService()->getService();
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

    !defined('JETON_MAKE_SUBDEF') ? define('JETON_MAKE_SUBDEF', 0x01) : '';
    !defined('JETON_WRITE_META_DOC') ? define('JETON_WRITE_META_DOC', 0x02) : '';
    !defined('JETON_WRITE_META_SUBDEF') ? define('JETON_WRITE_META_SUBDEF', 0x04) : '';
    !defined('JETON_WRITE_META') ? define('JETON_WRITE_META', 0x06) : '';

    if (\setup::is_installed())
    {
      $gatekeeper = \gatekeeper::getInstance();
      $gatekeeper->check_directory();
    }
    return;
  }

  /**
   * Load Configuration
   * 
   * @param type $environnement 
   */
  private function init()
  {
    if ($this->getConfiguration()->isInstalled())
    {
      if ($this->getConfiguration()->isDisplayingErrors())
      {
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);
      }
      else
      {
        ini_set('display_errors', 'off');
      }
    }
  }

  /**
   * Getter
   * 
   * @return \Registry 
   */
  public function getRegistry()
  {
    return $this['Registry'];
  }

  /**
   * Getter
   * 
   * @return \Registry 
   */
  public function getCache()
  {
    return $this['Cache'];
  }

  /**
   * Getter
   * 
   * @return \Doctrine\ORM\EntityManager 
   */
  public function getEntityManager()
  {
    return $this['EM'];
  }

  /**
   * Getter
   * 
   * @return \Twig_Environment
   */
  public function getTwig()
  {
    return $this['Twig'];
  }

  /**
   * Getter
   * 
   * @return Alchemy\Phrasea\Core\Version 
   */
  public function getVersion()
  {
    return $this['Version'];
  }

  /**
   * Getter
   * 
   * @return \Symfony\Component\Serializer\Serializer
   */
  public function getSerializer()
  {
    return $this['Serializer'];
  }

  /**
   * Tell if current seession is authenticated
   * 
   * @return boolean 
   */
  public function isAuthenticated()
  {
    $session = \Session_Handler::getInstance(\appbox::get_instance());

    return $session->is_authenticated();
  }

  /**
   * Return the current authenticated phraseanet user
   * 
   * @return \User_adapter 
   */
  public function getAuthenticatedUser()
  {
    $appbox = \appbox::get_instance();
    $session = \Session_Handler::getInstance($appbox);

    return \User_Adapter::getInstance($session->get_usr_id(), $appbox);
  }

  /**
   * Getter
   * 
   * @return Core\Configuration
   */
  public function getConfiguration()
  {
    return $this->configuration;
  }

  /**
   * Set Default application Timezone
   */
  protected function verifyTimezone()
  {
    if ($this->getRegistry()->is_set('GV_timezone'))
      date_default_timezone_set($this->getRegistry()->get('GV_timezone'));
    else
      date_default_timezone_set('Europe/Berlin');

    return;
  }

  protected $request;

  protected function getRequest()
  {
    if (!$this->request)
      $this->request = Request::createFromGlobals();

    return $this->request;
  }

  protected function enableLocales()
  {
    mb_internal_encoding("UTF-8");
    \phrasea::use_i18n($this->getRequest()->getLocale());

    return;
  }

  protected function enableEvents()
  {
    $events = \eventsmanager_broker::getInstance(\appbox::get_instance(), $this);
    $events->start();

    return;
  }

  /**
   * Initialiaze phraseanet log process
   * 
   * @return Core 
   */
  protected function initLoggers()
  {
    $php_log = $this->getRegistry()->get('GV_RootPath') . 'logs/php_error.log';

    ini_set('error_log', $php_log);

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
   * Return available language for phraseanet
   * 
   * @return Array 
   */
  public static function getAvailableLanguages()
  {
    return static::$availableLanguages;
  }

  /**
   * Set Language 
   * 
   */
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

    \Session_Handler::set_locale($this->getRequest()->getLocale());

    return;
  }

  /**
   * Finds the path to the file where the class is defined.
   *
   * @param string $class_name the name of the class we are looking for
   */
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

  /**
   * Register directory and namespaces for autoloading app classes
   *  
   */
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
        'Symfony' => realpath(__DIR__ . '/../../vendor/symfony/src'),
        'Doctrine\\ORM' => realpath(__DIR__ . '/../../vendor/doctrine2-orm/lib'),
        'Doctrine\\DBAL' => realpath(__DIR__ . '/../../vendor/doctrine2-orm/lib/vendor/doctrine-dbal/lib'),
        'Doctrine\\Common' => realpath(__DIR__ . '/../../vendor/doctrine2-orm/lib/vendor/doctrine-common/lib'),
        'Doctrine\\Common\\DataFixtures' => realpath(__DIR__ . '/../../vendor/data-fixtures/lib'),
        'Entities' => realpath(__DIR__ . '/../../Doctrine/'),
        'Repositories' => realpath(__DIR__ . '/../../Doctrine/'),
        'Proxies' => realpath(__DIR__ . '/../../Doctrine/'),
        'Doctrine\\Logger' => realpath(__DIR__ . '/../../'),
        'Monolog' => realpath(__DIR__ . '/../../vendor/Silex/vendor/monolog/src'),
    ));

    $loader->register();

    require_once __DIR__ . '/../../vendor/Silex/autoload.php';

    return;
  }

  /**
   * Initialize some PHP configuration variables
   * 
   */
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

  /**
   * Return the current working environnement (test, dev, prod etc ...)
   * 
   * @return string
   */
  public function getEnv()
  {
    return $this->configuration->getEnvironnement();
  }

}
