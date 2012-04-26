<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\Serializer;
use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../../vendor/pimple/pimple/lib/Pimple.php';

require_once __DIR__ . '/../../../vendor/symfony/yaml/Symfony/Component/Yaml/Yaml.php';
require_once __DIR__ . '/../../../vendor/symfony/yaml/Symfony/Component/Yaml/Parser.php';
require_once __DIR__ . '/../../../vendor/symfony/yaml/Symfony/Component/Yaml/Inline.php';
require_once __DIR__ . '/../../../vendor/symfony/yaml/Symfony/Component/Yaml/Unescaper.php';
require_once __DIR__ . '/../../../vendor/symfony/dependency-injection/Symfony/Component/DependencyInjection/ParameterBag/ParameterBagInterface.php';
require_once __DIR__ . '/../../../vendor/symfony/dependency-injection/Symfony/Component/DependencyInjection/ParameterBag/ParameterBag.php';

require_once __DIR__ . '/Core/Configuration/Specification.php';
require_once __DIR__ . '/Core/Configuration.php';
require_once __DIR__ . '/Core/Configuration/ApplicationSpecification.php';

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
    protected static $autoloader_initialized = false;

    /**
     *
     * @var Core\Configuration
     */
    private $configuration;

    public function __construct($environement = null)
    {
        $this->configuration = Core\Configuration::build(null, $environement);

        $core = $this;

        /**
         * Cache Autoload if it's not debug mode
         */
        static::initAutoloads($this->configuration->isInstalled() && ! $this->configuration->isDebug());

        $this->init();

        /**
         * Set version
         */
        $this['Version'] = $this->share(function() {
                return new Core\Version();
            });

        if ($this->configuration->isInstalled()) {
            $this['Registry'] = $this->share(function() use ($core) {
                    return \registry::get_instance($core);
                });

            \phrasea::start($this);
        } else {

            $this['Registry'] = $this->share(function() {
                    return new \Setup_Registry();
                });
        }


        $this['CacheService'] = $this->share(function() use ($core) {
                if ( ! file_exists(__DIR__ . '/../../../tmp/cache_registry.yml')) {
                    touch(__DIR__ . '/../../../tmp/cache_registry.yml');
                }

                $file = new \SplFileObject(__DIR__ . '/../../../tmp/cache_registry.yml');

                return new \Alchemy\Phrasea\Cache\Manager($core, $file);
            });

        /**
         * Set Entity Manager using configuration
         */
        $this['EM'] = $this->share(function() use ($core) {
                $serviceName = $core->getConfiguration()->getOrm();
                $configuration = $core->getConfiguration()->getService($serviceName);

                $Service = Core\Service\Builder::create($core, $configuration);

                return $Service->getDriver();
            });


        $this['Cache'] = $this->share(function() use ($core) {
                $serviceName = $core->getConfiguration()->getCache();

                return $core['CacheService']->get('MainCache', $serviceName)->getDriver();
            });

        $this['OpcodeCache'] = $this->share(function() use ($core) {
                $serviceName = $core->getConfiguration()->getOpcodeCache();

                return $core['CacheService']->get('OpcodeCache', $serviceName)->getDriver();
            });



        $this["Twig"] = $this->share(function() use ($core) {
                $serviceName = $core->getConfiguration()->getTemplating();
                $configuration = $core->getConfiguration()->getService($serviceName);

                $Service = Core\Service\Builder::create($core, $configuration);

                return $Service->getDriver();
            });


        $this['Serializer'] = $this->share(function() {
                $encoders = array(
                    'json' => new Serializer\Encoder\JsonEncoder()
                );

                return new Serializer\Serializer(array(), $encoders);
            });

        $this['monolog'] = $this->share(function () use ($core) {
                $logger = new \Monolog\Logger('Logger');

                $logger->pushHandler(new \Monolog\Handler\NullHandler());

                return $logger;
            });

        $this['media-alchemyst'] = $this->share(function () use ($core) {
                $conf = $core->getConfiguration()->has('media-alchemyst') ? $core->getConfiguration()->get('media-alchemyst') : new \Symfony\Component\DependencyInjection\ParameterBag\ParameterBag(array());

                $drivers = new \MediaAlchemyst\DriversContainer($conf, $core['monolog']);

                return new \MediaAlchemyst\Alchemyst($drivers);
            });

        self::initPHPConf();

        $this->initLoggers();

        $this->verifyTimeZone();

        $this->detectLanguage();

        $this->enableLocales();

        ! defined('JETON_MAKE_SUBDEF') ? define('JETON_MAKE_SUBDEF', 0x01) : '';
        ! defined('JETON_WRITE_META_DOC') ? define('JETON_WRITE_META_DOC', 0x02) : '';
        ! defined('JETON_WRITE_META_SUBDEF') ? define('JETON_WRITE_META_SUBDEF', 0x04) : '';
        ! defined('JETON_WRITE_META') ? define('JETON_WRITE_META', 0x06) : '';

        return;
    }

    /**
     * Load Configuration
     *
     * @param type $environnement
     */
    private function init()
    {
        if ($this->configuration->isInstalled()) {
            if ($this->configuration->isDisplayingErrors()) {
                ini_set('display_errors', 'on');
                error_reporting(E_ALL);
            } else {
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

    public function getCache()
    {
        return $this['Cache'];
    }

    public function getOpcodeCache()
    {
        return $this['OpcodeCache'];
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
        $session = \Session_Handler::getInstance(\appbox::get_instance($this));

        return $session->is_authenticated();
    }

    /**
     * Return the current authenticated phraseanet user
     *
     * @return \User_adapter
     */
    public function getAuthenticatedUser()
    {
        $appbox = \appbox::get_instance($this);
        $session = \Session_Handler::getInstance($appbox);

        if ($session->get_usr_id()) {
            return \User_Adapter::getInstance($session->get_usr_id(), $appbox);
        }

        return null;
    }

    /**
     * Getter
     *
     * @return \Alchemy\Phrasea\Core\Configuration
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
        if ( ! $this->request)
            $this->request = Request::createFromGlobals();

        return $this->request;
    }

    protected function enableLocales()
    {
        mb_internal_encoding("UTF-8");
        \phrasea::use_i18n($this->getRequest()->getLocale());

        return;
    }

    public function getLocale()
    {
        return $this->getRequest()->getLocale();
    }

    public function enableEvents()
    {
        $events = \eventsmanager_broker::getInstance(\appbox::get_instance($this), $this);
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

        if ($this->getRegistry()->get('GV_log_errors')) {
            ini_set('log_errors', 'on');
        } else {
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

        if (isset(static::$availableLanguages[$cookies->get('locale')])) {
            $this->getRequest()->setLocale($cookies->get('locale'));
        }

        \Session_Handler::set_locale($this->getRequest()->getLocale());

        return;
    }

    /**
     * Register directory and namespaces for autoloading app classes
     *
     */
    public static function initAutoloads($cacheAutoload = false)
    {
        if (static::$autoloader_initialized) {
            return;
        }

        require_once __DIR__ . '/Loader/Autoloader.php';

        if ($cacheAutoload === true) {
            try {
                require_once __DIR__ . '/Loader/CacheAutoloader.php';

                $prefix = 'class_';
                $namespace = md5(__DIR__);

                $loader = new Loader\CacheAutoloader($prefix, $namespace);
            } catch (\Exception $e) {
                //no op code cache available
                $loader = new Loader\Autoloader();
            }
        } else {
            $loader = new Loader\Autoloader();
        }

        $getComposerNamespaces = function() {
                return require realpath(__DIR__ . '/../../../vendor/.composer/autoload_namespaces.php');
            };

        foreach ($getComposerNamespaces() as $prefix => $path) {
            if (substr($prefix, -1) === '_')
                $loader->registerPrefix($prefix, $path);
            else
                $loader->registerNamespace($prefix, $path);
        }

        $loader->registerNamespaces(array(
            'Entities'         => realpath(__DIR__ . '/../../Doctrine/'),
            'Repositories'     => realpath(__DIR__ . '/../../Doctrine/'),
            'Proxies'          => realpath(__DIR__ . '/../../Doctrine/'),
            'Doctrine\\Logger' => realpath(__DIR__ . '/../../'),
            'Types'            => realpath(__DIR__ . "/../../Doctrine"),
            'PhraseaFixture'   => realpath(__DIR__ . "/../../conf.d"),
        ));

        $loader->register();

        set_include_path(
            get_include_path() . PATH_SEPARATOR . realpath(__DIR__ . '/../../../vendor/alchemy/gdata')
        );

        static::$autoloader_initialized = true;

        return;
    }

    /**
     * Initialize some PHP configuration variables
     *
     */
    public static function initPHPConf()
    {
        ini_set('output_buffering', '4096');

        if ((int) ini_get('memory_limit') < 2048) {
            ini_set('memory_limit', '2048M');
        }

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
