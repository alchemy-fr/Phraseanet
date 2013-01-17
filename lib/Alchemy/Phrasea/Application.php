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

use Alchemy\Phrasea\PhraseanetServiceProvider;
use Alchemy\Phrasea\Core\Event\Subscriber\Logout;
use Alchemy\Phrasea\Core\Provider\BrowserServiceProvider;
use Alchemy\Phrasea\Core\Provider\BorderManagerServiceProvider;
use Alchemy\Phrasea\Core\Provider\CacheServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider;
use Alchemy\Phrasea\Core\Provider\FtpServiceProvider;
use Alchemy\Phrasea\Core\Provider\GeonamesServiceProvider;
use Alchemy\Phrasea\Core\Provider\ORMServiceProvider;
use Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider;
use Alchemy\Phrasea\Core\Provider\TaskManagerServiceProvider;
use Alchemy\Phrasea\Core\Provider\UnicodeServiceProvider;
use FFMpeg\FFMpegServiceProvider;
use Grom\Silex\ImagineServiceProvider;
use MediaVorus\MediaVorusServiceProvider;
use MediaAlchemyst\MediaAlchemystServiceProvider;
use MediaAlchemyst\Driver\Imagine;
use Monolog\Handler\NullHandler;
use MP4Box\MP4BoxServiceProvider;
use Neutron\Silex\Provider\BadFaithServiceProvider;
use Neutron\Silex\Provider\FilesystemServiceProvider;
use PHPExiftool\PHPExiftoolServiceProvider;
use Silex\Application as SilexApplication;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Unoconv\UnoconvServiceProvider;
use XPDF\PdfToText;
use XPDF\XPDFServiceProvider;

use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class Application extends SilexApplication
{
    private static $availableLanguages = array(
        'de_DE' => 'Deutsch'
        , 'en_GB' => 'English'
        , 'fr_FR' => 'Français'
        , 'nl_NL' => 'Dutch'
    );
    private $environment;
    private $sessionCookieEnabled = true;

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function __construct($environment = 'prod')
    {
        parent::__construct();

        $this->environment = $environment;

        if ((int) ini_get('memory_limit') < 2048) {
            ini_set('memory_limit', '2048M');
        }

        error_reporting(E_ALL | E_STRICT);

        ini_set('display_errors', 'on');
        ini_set('output_buffering', '4096');
        ini_set('default_charset', 'UTF-8');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.auto_start', '0');
        ini_set('session.hash_function', '1');
        ini_set('session.hash_bits_per_character', '6');
        ini_set('session.cache_limiter', '');
        ini_set('allow_url_fopen', 'on');
        mb_internal_encoding("UTF-8");

        !defined('JETON_MAKE_SUBDEF') ? define('JETON_MAKE_SUBDEF', 0x01) : '';
        !defined('JETON_WRITE_META_DOC') ? define('JETON_WRITE_META_DOC', 0x02) : '';
        !defined('JETON_WRITE_META_SUBDEF') ? define('JETON_WRITE_META_SUBDEF', 0x04) : '';
        !defined('JETON_WRITE_META') ? define('JETON_WRITE_META', 0x06) : '';

        $this['charset'] = 'UTF-8';

        $this['debug'] = $this->share(function(Application $app) {
            return $app->getEnvironment() !== 'prod';
        });

        if ($this['debug'] === true) {
            ini_set('display_errors', 'on');
            if($this->getEnvironment() === 'dev') {
                ini_set('log_errors', 'on');
                ini_set('error_log', __DIR__ . '/../../../logs/php_error.log');
            }
        } else {
            ini_set('display_errors', 'off');
        }

        $this->register(new BadFaithServiceProvider());
        $this->register(new BorderManagerServiceProvider());
        $this->register(new BrowserServiceProvider());
        $this->register(new ConfigurationServiceProvider());
        $this->register(new ConfigurationTesterServiceProvider);
        $this->register(new CacheServiceProvider());
        $this->register(new ImagineServiceProvider());
        $this->register(new FFMpegServiceProvider());
        $this->register(new FilesystemServiceProvider());
        $this->register(new FtpServiceProvider());
        $this->register(new GeonamesServiceProvider);
        $this->register(new MediaAlchemystServiceProvider());
        $this->register(new MediaVorusServiceProvider());
        $this->register(new MonologServiceProvider());
        $this->register(new MP4BoxServiceProvider());
        $this->register(new ORMServiceProvider());
        $this->register(new PhraseanetServiceProvider());
        $this->register(new PHPExiftoolServiceProvider());
        $this->register(new SearchEngineServiceProvider());
        $this->register(new SessionServiceProvider(), array(
            'session.test' => $this->getEnvironment() == 'test'
        ));
        $this->register(new TaskManagerServiceProvider());
        $this->register(new UnoconvServiceProvider());
        $this->register(new UrlGeneratorServiceProvider());
        $this->register(new UnicodeServiceProvider());
        $this->register(new ValidatorServiceProvider());
        $this->register(new XPDFServiceProvider());

//        $this->register(new \Silex\Provider\HttpCacheServiceProvider());
//        $this->register(new \Silex\Provider\SecurityServiceProvider());
//        $this->register(new \Silex\Provider\SwiftmailerServiceProvider());

        $this['imagine.factory'] = $this->share(function(Application $app) {
            if ($app['phraseanet.registry']->get('GV_imagine_driver') != '') {
                return $app['phraseanet.registry']->get('GV_imagine_driver');
            }

            if (class_exists('\Gmagick')) {
                return Imagine::DRIVER_GMAGICK;
            }
            if (class_exists('\Imagick')) {
                return Imagine::DRIVER_IMAGICK;
            }
            if (extension_loaded('gd')) {
                return Imagine::DRIVER_GD;
            }

            throw new \RuntimeException('No Imagine driver available');
        });

        $this['monolog.name'] = 'Phraseanet logger';
        $this['monolog.handler'] = $this->share(function () {
            return new NullHandler();
        });

        $app = $this;
        $this['phraseanet.logger'] = $this->protect(function($databox) use ($app) {
            try {
                return \Session_Logger::load($app, $databox);
            } catch (\Exception_Session_LoggerNotFound $e) {
                return \Session_Logger::create($app, $databox, $app['browser']);
            }
        });

        $this->reinitUser();

        $this['date-formatter'] = $this->share(function(Application $app) {
            return new \phraseadate($app);
        });

        $this['xpdf.pdf2text'] = $this->share(
            $this->extend('xpdf.pdf2text', function(PdfToText $pdf2text, Application $app){
                if ($app['phraseanet.registry']->get('GV_pdfmaxpages')) {
                    $pdf2text->setPageQuantity($app['phraseanet.registry']->get('GV_pdfmaxpages'));
                }

                return $pdf2text;
            })
        );

        $this->register(new TwigServiceProvider(), array(
            'twig.options' => array(
                'cache'           => realpath(__DIR__ . '/../../../../../../tmp/cache_twig/'),
            )
        ));

        $this->setupTwig();

        $this['dispatcher']->addListener(KernelEvents::REQUEST, array($this, 'addLocale'), 255);
        $this['dispatcher']->addListener(KernelEvents::REQUEST, array($this, 'initSession'), 254);
        $this['dispatcher']->addListener(KernelEvents::RESPONSE, array($this, 'addUTF8Charset'), -128);
        $this['dispatcher']->addListener(KernelEvents::RESPONSE, array($this, 'disableCookiesIfRequired'), -256);
        $this['dispatcher']->addSubscriber(new Logout());

        $this['locale'] = $this->share(function(Application $app){
            return $app['phraseanet.registry']->get('GV_default_lng', 'en_GB');
        });

        $this['locale.I18n'] = function(Application $app){
            $data = explode('_', $app['locale']);

            return $data[0];
        };

        $this['locale.l10n'] = function(Application $app){
            $data = explode('_', $app['locale']);

            return $data[1];
        };
    }

    public function initSession(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if (false !== stripos($event->getRequest()->server->get('HTTP_USER_AGENT'), 'flash')
            && $event->getRequest()->getRequestUri() === '/prod/upload/') {

            if (null !== $sessionId = $event->getRequest()->request->get('php_session_id')) {

                $request = $event->getRequest();
                $request->cookies->set($this['session']->getName(), $sessionId);

                return $request;
            }
        }
    }

    public function addUTF8Charset(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $event->setResponse($event->getResponse()->setCharset('UTF-8'));
    }

    public function disableCookiesIfRequired(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if ($this->sessionCookieEnabled) {
            return;
        }

        $response = $event->getResponse();

        foreach ($response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY) as $cookie_domains) {
            foreach($cookie_domains as $cookie_paths) {
                foreach($cookie_paths as $cookie) {
                    $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
                }
            }
        }

        $event->setResponse($response);
    }

    public function addLocale(GetResponseEvent $event)
    {
        /**
         * add content negotiation here
         */
        $contentTypes = $event->getRequest()->getAcceptableContentTypes();
        $event->getRequest()->setRequestFormat(
            $event->getRequest()->getFormat(
                array_shift(
                    $contentTypes
                )
            )
        );

        $this['locale'] = $this->share(function(Application $app) use ($event) {
            $event->getRequest()->setDefaultLocale(
                $app['phraseanet.registry']->get('GV_default_lng', 'en_GB')
            );
            $event->getRequest()->setLocale(
                $app['phraseanet.registry']->get('GV_default_lng', 'en_GB')
            );

            $languages = $app->getAvailableLanguages();
            if ($event->getRequest()->cookies->has('locale')
                && isset($languages[$event->getRequest()->cookies->get('locale')])) {
                $event->getRequest()->setLocale($event->getRequest()->cookies->get('locale'));

                return $event->getRequest()->getLocale();
            }

            foreach($app['bad-faith']->headerLists['accept_language']->items as $language) {
                $code = $language->lang.'_'.$language->sublang;
                if(isset($languages[$code])) {

                    $event->getRequest()->setLocale($code);
                    return $event->getRequest()->getLocale();
                    break;
                }
            }

            return $event->getRequest()->getLocale();
        });

        \phrasea::use_i18n($this['locale']);
    }

    public function setupTwig()
    {
        $this['twig'] = $this->share(
            $this->extend('twig', function ($twig, $app) {

                if ($app['browser']->isTablet() || $app['browser']->isMobile()) {
                    $app['twig.loader.filesystem']->setPaths(array(
                        realpath(__DIR__ . '/../../../config/templates/mobile'),
                        realpath(__DIR__ . '/../../../templates/mobile'),
                    ));
                } else {
                    $app['twig.loader.filesystem']->setPaths(array(
                        realpath(__DIR__ . '/../../../config/templates/web'),
                        realpath(__DIR__ . '/../../../templates/web'),
                    ));
                }

                $twig->addGlobal('current_date', new \DateTime());

                $twig->addExtension(new \Twig_Extension_Core());
                $twig->addExtension(new \Twig_Extension_Optimizer());
                $twig->addExtension(new \Twig_Extension_Escaper());
                $twig->addExtension(new \Twig_Extensions_Extension_Debug());
                // add filter trans
                $twig->addExtension(new \Twig_Extensions_Extension_I18n());
                // add filter localizeddate
                $twig->addExtension(new \Twig_Extensions_Extension_Intl());
                // add filters truncate, wordwrap, nl2br
                $twig->addExtension(new \Twig_Extensions_Extension_Text());
                $twig->addExtension(new \Alchemy\Phrasea\Twig\JSUniqueID());

                $twig->addFilter('serialize', new \Twig_Filter_Function('serialize'));
                $twig->addFilter('stristr', new \Twig_Filter_Function('stristr'));
                $twig->addFilter('get_class', new \Twig_Filter_Function('get_class'));
                $twig->addFilter('stripdoublequotes', new \Twig_Filter_Function('stripdoublequotes'));
                $twig->addFilter('get_collection_logo', new \Twig_Filter_Function('collection::getLogo'));
                $twig->addFilter('floor', new \Twig_Filter_Function('floor'));
                $twig->addFilter('bas_names', new \Twig_Filter_Function('phrasea::bas_names'));
                $twig->addFilter('sbas_names', new \Twig_Filter_Function('phrasea::sbas_names'));
                $twig->addFilter('sbas_from_bas', new \Twig_Filter_Function('phrasea::sbasFromBas'));
                $twig->addFilter('key_exists', new \Twig_Filter_Function('array_key_exists'));
                $twig->addFilter('round', new \Twig_Filter_Function('round'));
                $twig->addFilter('formatOctets', new \Twig_Filter_Function('p4string::format_octets'));
                $twig->addFilter('base_from_coll', new \Twig_Filter_Function('phrasea::baseFromColl'));
                $twig->addFilter('AppName', new \Twig_Filter_Function('Alchemy\Phrasea\Controller\Admin\ConnectedUsers::appName'));

                return $twig;
            })
        );
    }

    /**
     * Tell if current a session is open
     *
     * @return boolean
     */
    public function isAuthenticated()
    {
        return $this['session']->has('usr_id');
    }

    /**
     * Returns an an array of available collection for offline queries
     *
     * @return array
     */
    public function getOpenCollections()
    {
        return array();
    }

    /**
     * Open user session
     *
     * @param \Session_Authentication_Interface $auth
     * @param integer $ses_id use previous phrasea session id
     * @throws \Exception_InternalServerError
     */
    public function openAccount(\Session_Authentication_Interface $auth, $ses_id = null)
    {
        $user = $auth->get_user();

        $this['session']->clear();
        $this['session']->set('usr_id', $user->get_id());

        $session = new \Entities\Session();
        $session->setBrowserName($this['browser']->getBrowser())
            ->setBrowserVersion($this['browser']->getVersion())
            ->setPlatform($this['browser']->getPlatform())
            ->setUserAgent($this['browser']->getUserAgent())
            ->setUsrId($user->get_id());

        $this['EM']->persist($session);
        $this['EM']->flush();

        $this['session']->set('session_id', $session->getId());

        foreach ($user->ACL()->get_granted_sbas() as $databox) {
            \cache_databox::insertClient($this, $databox);
        }
        $this->reinitUser();
    }

    private function reinitUser()
    {
        $this['phraseanet.user'] = $this->share(function(Application $app) {
            if ($app->isAuthenticated()) {
                return \User_Adapter::getInstance($app['session']->get('usr_id'), $app);
            }

            return null;
        });
    }

    /**
     * Closes user session
     */
    public function closeAccount()
    {
        $this['session']->clear();
        $this->reinitUser();

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

    public function disableCookies()
    {
        $this['session.test'] = true;
        $this->sessionCookieEnabled = false;
    }
}

