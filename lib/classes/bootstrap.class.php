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
class bootstrap
{

  protected static function define_dirs()
  {
    $root = dirname(dirname(dirname(__FILE__)));
    if (!defined('__LIBDIR__'))
      define('__LIBDIR__', $root . '/lib');
    if (!defined('__CUSTOMDIR__'))
      define('__CUSTOMDIR__', $root . '/config');
    self::require_essentials();
  }

  public static function set_php_configuration()
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

  public static function execute()
  {
    self::define_dirs();
    self::require_essentials();

    $registry = registry::get_instance();

    if ($registry->get('GV_RootPath') === false)
    {
      $registry->set('GV_RootPath', dirname(__FILE__) . '/../../');
      $registry->set('GV_debug', true);
    }

    self::set_php_configuration();

    ini_set('error_log', $registry->get('GV_RootPath') . 'logs/php_error.log');

    if ($registry->get('GV_debug'))
    {
      ini_set('display_errors', 'on');
      ini_set('display_startup_errors', 'on');
    }
    else
    {
      ini_set('display_errors', 'off');
      ini_set('display_startup_errors', 'off');
    }

    if ($registry->get('GV_log_errors'))
    {
      ini_set('log_errors', 'on');
    }
    else
    {
      ini_set('log_errors', 'off');
    }

    self::register_autoloads();
    self::init_functions();

    define('JETON_MAKE_SUBDEF', 0x01);
    define('JETON_WRITE_META_DOC', 0x02);
    define('JETON_WRITE_META_SUBDEF', 0x04);
    define('JETON_WRITE_META', 0x06);

    $gatekeeper = gatekeeper::getInstance();
    $gatekeeper->check_directory();
  }

  protected static function phrasea_autoload($class_name)
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

  protected static function require_essentials()
  {
    require_once __LIBDIR__ . '/version.inc';
    require_once __LIBDIR__ . '/classes/cache/opcode/interface.class.php';
    require_once __LIBDIR__ . '/classes/cache/cacheableInterface.class.php';
    require_once __LIBDIR__ . '/classes/cache/opcode/adapter.class.php';
    require_once __LIBDIR__ . '/classes/registryInterface.class.php';
    require_once __LIBDIR__ . '/classes/registry.class.php';
    require_once __LIBDIR__ . '/classes/Session/Storage/Interface.class.php';
    require_once __LIBDIR__ . '/classes/Session/Storage/Abstract.class.php';
    require_once __LIBDIR__ . '/classes/Session/Storage/PHPSession.class.php';
    require_once __LIBDIR__ . '/classes/Session/Storage/CommandLine.class.php';
    require_once __LIBDIR__ . '/classes/base.class.php';
    require_once __LIBDIR__ . '/classes/appbox.class.php';
    require_once __LIBDIR__ . '/classes/Session/Handler.class.php';
    require_once __LIBDIR__ . '/classes/phrasea.class.php';
    require_once __LIBDIR__ . '/classes/User/Interface.class.php';
    require_once __LIBDIR__ . '/classes/User/Adapter.class.php';
    require_once __LIBDIR__ . '/classes/eventsmanager/eventAbstract.class.php';
    require_once __LIBDIR__ . '/classes/eventsmanager/notifyAbstract.class.php';
    require_once __LIBDIR__ . '/classes/eventsmanager/broker.class.php';
    require_once __LIBDIR__ . '/classes/gatekeeper.class.php';
    require_once __LIBDIR__ . '/classes/http/request.class.php';
    require_once __LIBDIR__ . '/classes/p4string.class.php';

    require_once __LIBDIR__ . '/classes/connection/interface.class.php';
    require_once __LIBDIR__ . '/classes/connection/abstract.class.php';
    require_once __LIBDIR__ . '/classes/connection/pdo.class.php';
    require_once __LIBDIR__ . '/classes/connection/pdoStatementDebugger.class.php';
    require_once __LIBDIR__ . '/classes/connection.class.php';
  }

  public static function register_autoloads()
  {
    self::define_dirs();

    spl_autoload_register(array('bootstrap', 'phrasea_autoload'));

    require_once __LIBDIR__ . '/vendor/Twig/lib/Twig/Autoloader.php';
    require_once __LIBDIR__ . '/vendor/Twig-extensions/lib/Twig/Extensions/Autoloader.php';
    require_once __LIBDIR__ . '/vendor/symfony/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';

    Twig_Autoloader::register();
    Twig_Extensions_Autoloader::register();
     
    /**
     * Load symfony components needed
     */
    $loader = new Symfony\Component\ClassLoader\UniversalClassLoader();
    $loader->registerNamespaces(array(
        'Symfony\\Component\\Yaml' => __LIBDIR__ . '/vendor/symfony/src',
        'Symfony\\Component\\Console' => __LIBDIR__ . '/vendor/symfony/src',
        'Symfony\\Component\\BrowserKit' => __LIBDIR__ . '/vendor/symfony/src',
    ));
    $loader->register();

    require_once __LIBDIR__ . '/vendor/Silex/autoload.php';
  }

  protected static function init_functions()
  {
    $registry = registry::get_instance();
    if ($registry->is_set('GV_timezone'))
      date_default_timezone_set($registry->get('GV_timezone'));
    else
      date_default_timezone_set('Europe/Berlin');

    phrasea::start();
    User_Adapter::detectlanguage($registry);

    $appbox = appbox::get_instance();
    $session = $appbox->get_session();

    if (Session_Handler::get_cookie('locale') !== Session_Handler::get_locale())
    {
      $avLanguages = User_Adapter::detectlanguage($registry, Session_Handler::get_cookie('locale'));
    }

    mb_internal_encoding("UTF-8");
    phrasea::use_i18n(Session_Handler::get_locale());
    phrasea::load_events();
  }

}
