<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea;

use Alchemy\Phrasea\Controller\Lightbox;
use Alchemy\Phrasea\Controller\Datafiles;
use Alchemy\Phrasea\Controller\Permalink;
use Alchemy\Phrasea\Controller\Admin\Collection;
use Alchemy\Phrasea\Controller\Admin\ConnectedUsers;
use Alchemy\Phrasea\Controller\Admin\Dashboard;
use Alchemy\Phrasea\Controller\Admin\Databox;
use Alchemy\Phrasea\Controller\Admin\Databoxes;
use Alchemy\Phrasea\Controller\Admin\Fields;
use Alchemy\Phrasea\Controller\Admin\Publications;
use Alchemy\Phrasea\Controller\Admin\Root as AdminRoot;
use Alchemy\Phrasea\Controller\Admin\Setup;
use Alchemy\Phrasea\Controller\Admin\SearchEngine;
use Alchemy\Phrasea\Controller\Admin\Subdefs;
use Alchemy\Phrasea\Controller\Admin\TaskManager;
use Alchemy\Phrasea\Controller\Admin\Users;
use Alchemy\Phrasea\Controller\Client\Baskets as ClientBasket;
use Alchemy\Phrasea\Controller\Client\Root as ClientRoot;
use Alchemy\Phrasea\Controller\Minifier;
use Alchemy\Phrasea\Controller\Prod\BasketController;
use Alchemy\Phrasea\Controller\Prod\Bridge;
use Alchemy\Phrasea\Controller\Prod\Download;
use Alchemy\Phrasea\Controller\Prod\DoDownload;
use Alchemy\Phrasea\Controller\Prod\Edit;
use Alchemy\Phrasea\Controller\Prod\Export;
use Alchemy\Phrasea\Controller\Prod\Feed;
use Alchemy\Phrasea\Controller\Prod\Language;
use Alchemy\Phrasea\Controller\Prod\Lazaret;
use Alchemy\Phrasea\Controller\Prod\MoveCollection;
use Alchemy\Phrasea\Controller\Prod\Order;
use Alchemy\Phrasea\Controller\Prod\Printer;
use Alchemy\Phrasea\Controller\Prod\Push;
use Alchemy\Phrasea\Controller\Prod\Query;
use Alchemy\Phrasea\Controller\Prod\Property;
use Alchemy\Phrasea\Controller\Prod\Records;
use Alchemy\Phrasea\Controller\Prod\Root as Prod;
use Alchemy\Phrasea\Controller\Prod\Share;
use Alchemy\Phrasea\Controller\Prod\Story;
use Alchemy\Phrasea\Controller\Prod\Tools;
use Alchemy\Phrasea\Controller\Prod\Tooltip;
use Alchemy\Phrasea\Controller\Prod\TOU;
use Alchemy\Phrasea\Controller\Prod\Upload;
use Alchemy\Phrasea\Controller\Prod\UsrLists;
use Alchemy\Phrasea\Controller\Prod\WorkZone;
use Alchemy\Phrasea\Controller\Report\Activity as ReportActivity;
use Alchemy\Phrasea\Controller\Report\Informations as ReportInformations;
use Alchemy\Phrasea\Controller\Report\Export as ReportExport;
use Alchemy\Phrasea\Controller\Report\Root as ReportRoot;
use Alchemy\Phrasea\Controller\Root\Account;
use Alchemy\Phrasea\Controller\Root\Developers;
use Alchemy\Phrasea\Controller\Root\Login;
use Alchemy\Phrasea\Controller\Root\Root;
use Alchemy\Phrasea\Controller\Root\RSSFeeds;
use Alchemy\Phrasea\Controller\Root\Session;
use Alchemy\Phrasea\Controller\Setup as SetupController;
use Alchemy\Phrasea\Controller\Thesaurus\Thesaurus;
use Alchemy\Phrasea\Controller\Thesaurus\Xmlhttp as ThesaurusXMLHttp;
use Alchemy\Phrasea\Controller\Utils\ConnectionTest;
use Alchemy\Phrasea\Controller\Utils\PathFileTest;
use Alchemy\Phrasea\Controller\User\Notifications;
use Alchemy\Phrasea\Controller\User\Preferences;
use Alchemy\Phrasea\Core\PhraseaExceptionHandler;
use Alchemy\Phrasea\Core\Event\Subscriber\LogoutSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaLocaleSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\MaintenanceSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\CookiesDisablerSubscriber;
use Alchemy\Phrasea\Core\Middleware\BasketMiddlewareProvider;
use Alchemy\Phrasea\Core\Provider\ACLServiceProvider;
use Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider;
use Alchemy\Phrasea\Core\Provider\BrowserServiceProvider;
use Alchemy\Phrasea\Core\Provider\BorderManagerServiceProvider;
use Alchemy\Phrasea\Core\Provider\CacheServiceProvider;
use Alchemy\Phrasea\Core\Provider\CacheConnectionServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConvertersServiceProvider;
use Alchemy\Phrasea\Core\Provider\FileServeServiceProvider;
use Alchemy\Phrasea\Core\Provider\FeedServiceProvider;
use Alchemy\Phrasea\Core\Provider\FtpServiceProvider;
use Alchemy\Geonames\GeonamesServiceProvider;
use Alchemy\Phrasea\Core\Provider\InstallerServiceProvider;
use Alchemy\Phrasea\Core\Provider\JMSSerializerServiceProvider;
use Alchemy\Phrasea\Core\Provider\LocaleServiceProvider;
use Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider;
use Alchemy\Phrasea\Core\Provider\NotificationDelivererServiceProvider;
use Alchemy\Phrasea\Core\Provider\ORMServiceProvider;
use Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider;
use Alchemy\Phrasea\Core\Provider\PluginServiceProvider;
use Alchemy\Phrasea\Core\Provider\PhraseaVersionServiceProvider;
use Alchemy\Phrasea\Core\Provider\RegistrationServiceProvider;
use Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider;
use Alchemy\Phrasea\Core\Provider\TasksServiceProvider;
use Alchemy\Phrasea\Core\Provider\TemporaryFilesystemServiceProvider;
use Alchemy\Phrasea\Core\Provider\TokensServiceProvider;
use Alchemy\Phrasea\Core\Provider\UnicodeServiceProvider;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Twig\JSUniqueID;
use Alchemy\Phrasea\Twig\Camelize;
use Alchemy\Phrasea\Twig\BytesConverter;
use FFMpeg\FFMpegServiceProvider;
use Neutron\Silex\Provider\ImagineServiceProvider;
use MediaVorus\MediaVorusServiceProvider;
use MediaVorus\Utils\RawImageMimeTypeGuesser;
use MediaVorus\Utils\PostScriptMimeTypeGuesser;
use MediaVorus\Utils\AudioMimeTypeGuesser;
use MediaVorus\Utils\VideoMimeTypeGuesser;
use MediaAlchemyst\MediaAlchemystServiceProvider;
use Monolog\Handler\NullHandler;
use MP4Box\MP4BoxServiceProvider;
use Neutron\Silex\Provider\FilesystemServiceProvider;
use Neutron\ReCaptcha\ReCaptchaServiceProvider;
use PHPExiftool\PHPExiftoolServiceProvider;
use Silex\Application as SilexApplication;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Unoconv\UnoconvServiceProvider;
use XPDF\PdfToText;
use XPDF\XPDFServiceProvider;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Exception\FormException;

class Application extends SilexApplication
{
    private static $availableLanguages = array(
        'de_DE' => 'Deutsch',
        'en_GB' => 'English',
        'fr_FR' => 'Français',
        'nl_NL' => 'Dutch',
    );
    private static $flashTypes = array('warning', 'info', 'success', 'error');
    private $environment;

    const ENV_DEV = 'dev';
    const ENV_PROD = 'prod';
    const ENV_TEST = 'test';

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function __construct($environment = self::ENV_PROD)
    {
        parent::__construct();

        error_reporting(-1);

        $this['root.path'] = realpath(__DIR__ . '/../../..');
        $this->environment = $environment;

        mb_internal_encoding("UTF-8");

        !defined('JETON_MAKE_SUBDEF') ? define('JETON_MAKE_SUBDEF', 0x01) : '';
        !defined('JETON_WRITE_META_DOC') ? define('JETON_WRITE_META_DOC', 0x02) : '';
        !defined('JETON_WRITE_META_SUBDEF') ? define('JETON_WRITE_META_SUBDEF', 0x04) : '';
        !defined('JETON_WRITE_META') ? define('JETON_WRITE_META', 0x06) : '';

        $this['charset'] = 'UTF-8';

        $this['debug'] = $this->share(function (Application $app) {
            return Application::ENV_PROD !== $app->getEnvironment();
        });

        if ($this['debug'] === true) {
            ini_set('log_errors', 'on');
            ini_set('error_log', $this['root.path'] . '/logs/php_error.log');
        }

        $this->register(new BasketMiddlewareProvider());

        $this->register(new ACLServiceProvider());
        $this->register(new AuthenticationManagerServiceProvider());
        $this->register(new BorderManagerServiceProvider());
        $this->register(new BrowserServiceProvider());
        $this->register(new ConfigurationServiceProvider());
        $this->register(new ConfigurationTesterServiceProvider);
        $this->register(new ConvertersServiceProvider());
        $this->register(new RegistrationServiceProvider());
        $this->register(new CacheServiceProvider());
        $this->register(new CacheConnectionServiceProvider());
        $this->register(new ImagineServiceProvider());
        $this->register(new JMSSerializerServiceProvider());
        $this->register(new FFMpegServiceProvider());
        $this->register(new FeedServiceProvider());
        $this->register(new FilesystemServiceProvider());
        $this->register(new FtpServiceProvider());
        $this->register(new GeonamesServiceProvider());
        $this['geonames.server-uri'] = $this->share(function (Application $app) {
            return $app['phraseanet.registry']->get('GV_i18n_service', 'https://geonames.alchemyasp.com/');
        });

        $this->register(new MediaAlchemystServiceProvider());
        $this['media-alchemyst.configuration'] = $this->share(function (Application $app) {
            $configuration = array();

            foreach (array(
                    'swftools.pdf2swf.binaries'    => 'pdf2swf_binary',
                    'swftools.swfrender.binaries'  => 'swf_render_binary',
                    'swftools.swfextract.binaries' => 'swf_extract_binary',
                    'unoconv.binaries'             => 'unoconv_binary',
                    'mp4box.binaries'              => 'mp4box_binary',
                    'gs.binaries'                  => 'ghostscript_binary',
                    'ffmpeg.ffmpeg.binaries'       => 'ffmpeg_binary',
                    'ffmpeg.ffprobe.binaries'      => 'ffprobe_binary',
                    'ffmpeg.ffmpeg.timeout'        => 'ffmpeg_timeout',
                    'ffmpeg.ffprobe.timeout'       => 'ffprobe_timeout',
                    'gs.timeout'                   => 'gs_timeout',
                    'mp4box.timeout'               => 'mp4box_timeout',
                    'swftools.timeout'             => 'swftools_timeout',
                    'unoconv.timeout'              => 'unoconv_timeout',
            ) as $parameter => $key) {
                if (isset($this['phraseanet.configuration']['binaries'][$key])) {
                    $configuration[$parameter] = $this['phraseanet.configuration']['binaries'][$key];
                }
            }

            $imagineDriver = $app['phraseanet.registry']->get('GV_imagine_driver');

            $configuration['ffmpeg.threads'] = $app['phraseanet.registry']->get('GV_ffmpeg_threads');
            $configuration['imagine.driver'] = $imagineDriver ?: null;

            return $configuration;
        });
        $this['media-alchemyst.logger'] = $this->share(function (Application $app) {
            return $app['monolog'];
        });

        $this->register(new MediaVorusServiceProvider());
        $this->register(new MonologServiceProvider());
        $this['monolog.name'] = 'Phraseanet logger';
        $this['monolog.handler'] = $this->share(function () {
            return new NullHandler();
        });
        $this->register(new MP4BoxServiceProvider());
        $this->register(new NotificationDelivererServiceProvider());
        $this->register(new ORMServiceProvider());
        $this->register(new ManipulatorServiceProvider());
        $this->register(new InstallerServiceProvider());
        $this->register(new PhraseanetServiceProvider());
        $this->register(new PhraseaVersionServiceProvider());
        $this->register(new PHPExiftoolServiceProvider());
        $this->register(new ReCaptchaServiceProvider());

        $this['recaptcha.public-key'] = $this->share(function (Application $app) {
            if ($app['phraseanet.registry']->get('GV_captchas')) {
                return $app['phraseanet.registry']->get('GV_captcha_public_key');
            }
        });
        $this['recaptcha.private-key'] = $this->share(function (Application $app) {
            if ($app['phraseanet.registry']->get('GV_captchas')) {
                return $app['phraseanet.registry']->get('GV_captcha_private_key');
            }
        });

        $this->register(new SearchEngineServiceProvider());
        $this->register(new SessionServiceProvider(), array(
            'session.test' => $this->getEnvironment() === static::ENV_TEST
        ));
        $this->register(new ServiceControllerServiceProvider());
        $this->register(new SwiftmailerServiceProvider());
        $this->register(new TasksServiceProvider());
        $this->register(new TemporaryFilesystemServiceProvider());
        $this->register(new TokensServiceProvider());
        $this->register(new TwigServiceProvider(), array(
            'twig.options' => array(
                'cache'           => $this['root.path'] . '/tmp/cache_twig/',
            ),
            'twig.form.templates' => array('login/common/form_div_layout.html.twig')
        ));
        $this->register(new FormServiceProvider());

        $this->setupTwig();
        $this->register(new UnoconvServiceProvider());
        $this->register(new UrlGeneratorServiceProvider());
        $this->setupUrlGenerator();
        $this->register(new UnicodeServiceProvider());
        $this->register(new ValidatorServiceProvider());
        $this->register(new XPDFServiceProvider());
        $this->register(new FileServeServiceProvider());
        $this->register(new ManipulatorServiceProvider());
        $this->register(new PluginServiceProvider());

        $this['phraseanet.exception_handler'] = $this->share(function ($app) {
            return PhraseaExceptionHandler::register($app['debug']);
        });

        $this['swiftmailer.transport'] = $this->share(function ($app) {
            if ($app['phraseanet.registry']->get('GV_smtp')) {
                $transport = new \Swift_Transport_EsmtpTransport(
                    $app['swiftmailer.transport.buffer'],
                    array($app['swiftmailer.transport.authhandler']),
                    $app['swiftmailer.transport.eventdispatcher']
                );

                $encryption = null;

                if (in_array($app['phraseanet.registry']->get('GV_smtp_secure'), array('ssl', 'tls'))) {
                    $encryption = $app['phraseanet.registry']->get('GV_smtp_secure');
                }

                $options = $app['swiftmailer.options'] = array_replace(array(
                    'host'       => $app['phraseanet.registry']->get('GV_smtp_host'),
                    'port'       => $app['phraseanet.registry']->get('GV_smtp_port'),
                    'username'   => $app['phraseanet.registry']->get('GV_smtp_user'),
                    'password'   => $app['phraseanet.registry']->get('GV_smtp_password'),
                    'encryption' => $encryption,
                    'auth_mode'  => null,
                ), $app['swiftmailer.options']);

                $transport->setHost($options['host']);
                $transport->setPort($options['port']);
                // tls or ssl
                $transport->setEncryption($options['encryption']);

                if ($app['phraseanet.registry']->get('GV_smtp_auth')) {
                    $transport->setUsername($options['username']);
                    $transport->setPassword($options['password']);
                    $transport->setAuthMode($options['auth_mode']);
                }
            } else {
                $transport = new \Swift_Transport_MailTransport(
                    new \Swift_Transport_SimpleMailInvoker(),
                    $app['swiftmailer.transport.eventdispatcher']
                );
            }

            return $transport;
        });

        $this['imagine.factory'] = $this->share(function (Application $app) {
            if ($app['phraseanet.registry']->get('GV_imagine_driver') != '') {
                return $app['phraseanet.registry']->get('GV_imagine_driver');
            }
            if (class_exists('\Gmagick')) {
                return 'gmagick';
            }
            if (class_exists('\Imagick')) {
                return 'imagick';
            }
            if (extension_loaded('gd')) {
                return 'gd';
            }

            throw new \RuntimeException('No Imagine driver available');
        });

        $app = $this;
        $this['phraseanet.logger'] = $this->protect(function ($databox) use ($app) {
            try {
                return \Session_Logger::load($app, $databox);
            } catch (\Exception_Session_LoggerNotFound $e) {
                return \Session_Logger::create($app, $databox, $app['browser']);
            }
        });

        $this['date-formatter'] = $this->share(function (Application $app) {
            return new \phraseadate($app);
        });

        $this['xpdf.pdftotext'] = $this->share(
            $this->extend('xpdf.pdftotext', function (PdfToText $pdftotext, Application $app) {
                if ($app['phraseanet.registry']->get('GV_pdfmaxpages')) {
                    $pdftotext->setPageQuantity($app['phraseanet.registry']->get('GV_pdfmaxpages'));
                }

                return $pdftotext;
            })
        );

        $this['dispatcher'] = $this->share(
            $this->extend('dispatcher', function ($dispatcher, Application $app) {
                $dispatcher->addListener(KernelEvents::REQUEST, array($app, 'initSession'), 254);
                $dispatcher->addListener(KernelEvents::RESPONSE, array($app, 'addUTF8Charset'), -128);
                $dispatcher->addSubscriber(new LogoutSubscriber());
                $dispatcher->addSubscriber(new PhraseaLocaleSubscriber($app));
                $dispatcher->addSubscriber(new MaintenanceSubscriber($app));
                $dispatcher->addSubscriber(new CookiesDisablerSubscriber($app));

                return $dispatcher;
            })
        );

        $this['log.channels'] = array('monolog', 'task-manager.logger');

        $this->register(new LocaleServiceProvider());

        $this->mount('/include/minify/', new Minifier());
        $this->mount('/permalink/', new Permalink());
        $this->mount('/lightbox/', new Lightbox());

        $guesser = MimeTypeGuesser::getInstance();

        $guesser->register(new FileBinaryMimeTypeGuesser());
        $guesser->register(new RawImageMimeTypeGuesser());
        $guesser->register(new PostScriptMimeTypeGuesser());
        $guesser->register(new AudioMimeTypeGuesser());
        $guesser->register(new VideoMimeTypeGuesser());

        $app['plugins.directory'] = $app->share(function () {
            $dir = __DIR__ . '/../../../plugins';

            if (is_dir($dir)) {
                return realpath($dir);
            }

            return $dir;
        });
    }

    /**
     * Loads Phraseanet plugins
     */
    public function loadPlugins()
    {
        call_user_func(function ($app) {
            require $app['plugins.directory'] . '/services.php';
        }, $this);
    }

    /**
     * Returns a form.
     *
     * @see FormFactory::create()
     *
     * @param string|FormTypeInterface $type    The type of the form
     * @param mixed                    $data    The initial data
     * @param array                    $options The options
     * @param FormBuilderInterface     $parent  The parent builder
     *
     * @return FormInterface The form named after the type
     *
     * @throws FormException if any given option is not applicable to the given type
     */
    public function form($type = 'form', $data = null, array $options = array(), FormBuilderInterface $parent = null)
    {
        return $this['form.factory']->create($type, $data, $options, $parent);
    }

    /**
     * Generates a path from the given parameters.
     *
     * @param string $route      The name of the route
     * @param mixed  $parameters An array of parameters
     *
     * @return string The generated path
     */
    public function path($route, $parameters = array())
    {
        return $this['url_generator']->generate($route, $parameters, UrlGenerator::ABSOLUTE_PATH);
    }

    /**
     * Returns a redirect response with a relative path related to a route name.
     *
     * @param string $route      The name of the route
     * @param mixed  $parameters An array of parameters
     *
     * @return RedirectResponse
     */
    public function redirectPath($route, $parameters = array())
    {
        return $this->redirect($this->path($route, $parameters));
    }

    /**
     * Returns an absolute URL from the given parameters.
     *
     * @param string $route      The name of the route
     * @param mixed  $parameters An array of parameters
     *
     * @return string The generated URL
     */
    public function url($route, $parameters = array())
    {
        return $this['url_generator']->generate($route, $parameters, UrlGenerator::ABSOLUTE_URL);
    }

    /**
     * Returns a redirect response with a fully qualified URI related to a route name.
     *
     * @param string $route      The name of the route
     * @param mixed  $parameters An array of parameters
     *
     * @return RedirectResponse
     */
    public function redirectUrl($route, $parameters = array())
    {
        return $this->redirect($this->url($route, $parameters));
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

        $event->getResponse()->setCharset('UTF-8');
    }

    private function setupUrlGenerator()
    {
        $this['url_generator'] = $this->share($this->extend('url_generator', function ($urlGenerator, $app) {
            if ($app['phraseanet.configuration']->isSetup()) {
                $data = parse_url($app['phraseanet.configuration']['main']['servername']);

                if (isset($data['scheme'])) {
                    $urlGenerator->getContext()->setScheme($data['scheme']);
                }
                if (isset($data['host'])) {
                    $urlGenerator->getContext()->setHost($data['host']);
                }
            }

            return $urlGenerator;
        }));
    }

    public function setupTwig()
    {
        $this['twig'] = $this->share(
            $this->extend('twig', function ($twig, $app) {
                $paths = require $app['plugins.directory'] . '/twig-paths.php';

                if ($app['browser']->isTablet() || $app['browser']->isMobile()) {
                    $paths[] = $app['root.path'] . '/config/templates/mobile';
                    $paths[] = $app['root.path'] . '/templates/mobile';
                    $paths['phraseanet'] = $app['root.path'] . '/config/templates/mobile';
                    $paths['phraseanet'] = $app['root.path'] . '/templates/mobile';
                }

                $paths[] = $app['root.path'] . '/config/templates/web';
                $paths[] = $app['root.path'] . '/templates/web';
                $paths['phraseanet'] = $app['root.path'] . '/config/templates/web';
                $paths['phraseanet'] = $app['root.path'] . '/templates/web';

                foreach ($paths as $namespace => $path) {
                    if (!is_int($namespace)) {
                        $app['twig.loader.filesystem']->addPath($path, $namespace);
                    } else {
                        $app['twig.loader.filesystem']->addPath($path);
                    }
                }

                $twig->addGlobal('current_date', new \DateTime());

                $twig->addExtension(new \Twig_Extension_Core());
                $twig->addExtension(new \Twig_Extension_Optimizer());
                $twig->addExtension(new \Twig_Extension_Escaper());

                // add filter trans
                $twig->addExtension(new \Twig_Extensions_Extension_I18n());
                // add filter localizeddate
                $twig->addExtension(new \Twig_Extensions_Extension_Intl());
                // add filters truncate, wordwrap, nl2br
                $twig->addExtension(new \Twig_Extensions_Extension_Text());
                $twig->addExtension(new JSUniqueID());
                $twig->addExtension(new Camelize());
                $twig->addExtension(new BytesConverter());

                $twig->addFilter('serialize', new \Twig_Filter_Function('serialize'));
                $twig->addFilter('stristr', new \Twig_Filter_Function('stristr'));
                $twig->addFilter('get_class', new \Twig_Filter_Function('get_class'));
                $twig->addFilter('stripdoublequotes', new \Twig_Filter_Function('stripdoublequotes'));
                $twig->addFilter('get_collection_logo', new \Twig_Filter_Function('collection::getLogo'));
                $twig->addFilter('floor', new \Twig_Filter_Function('floor'));
                $twig->addFilter('min', new \Twig_Filter_Function('min'));
                $twig->addFilter('bas_labels', new \Twig_Filter_Function('phrasea::bas_labels'));
                $twig->addFilter('sbas_names', new \Twig_Filter_Function('phrasea::sbas_names'));
                $twig->addFilter('sbas_labels', new \Twig_Filter_Function('phrasea::sbas_labels'));
                $twig->addFilter('sbas_from_bas', new \Twig_Filter_Function('phrasea::sbasFromBas'));
                $twig->addFilter('key_exists', new \Twig_Filter_Function('array_key_exists'));
                $twig->addFilter('round', new \Twig_Filter_Function('round'));
                $twig->addFilter('count', new \Twig_Filter_Function('count'));
                $twig->addFilter('formatOctets', new \Twig_Filter_Function('p4string::format_octets'));
                $twig->addFilter('base_from_coll', new \Twig_Filter_Function('phrasea::baseFromColl'));
                $twig->addFilter('AppName', new \Twig_Filter_Function('Alchemy\Phrasea\Controller\Admin\ConnectedUsers::appName'));
                $twig->addFilter(new \Twig_SimpleFilter('escapeSimpleQuote', function ($value) {
                    $ret = str_replace("'", "\'", $value);

                    return $ret;
                }));
                $twig->addFilter(new \Twig_SimpleFilter('escapeDoubleQuote', function ($value) {
                    return str_replace('"', '\"', $value);
                }));

                return $twig;
            })
        );
    }

    /**
     * Adds a flash message for type.
     *
     * In Phraseanet, valid types are "warning", "info", "success" and "error"
     *
     * @param string $type
     * @param string $message
     *
     * @return Application
     *
     * @throws InvalidArgumentException In case the type is not valid
     */
    public function addFlash($type, $message)
    {
        if (!in_array($type, self::$flashTypes)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid flash message type `%s`, valid type are %s', $type, implode(', ', self::$flashTypes)
            ));
        }

        $this['session']->getFlashBag()->add($type, $message);

        return $this;
    }

    /**
     * Gets and clears flash from the stack.
     *
     * @param string $type
     * @param array  $default Default value if $type does not exist.
     *
     * @return array
     */
    public function getFlash($type, array $default = array())
    {
        return $this['session']->getFlashBag()->get($type, $default);
    }

    /**
     * Adds a temporary unlock data for an account-locked user
     *
     * @param integer $data
     */
    public function addUnlockAccountData($data)
    {
        $this['session']->set('unlock_account_data', $data);
    }

    /**
     * Returns the temporary unlock account data
     *
     * @return null|integer
     */
    public function getUnlockAccountData()
    {
        if ($this['session']->has('unlock_account_data')) {
            return $this['session']->remove('unlock_account_data');
        }

        return null;
    }

    /**
     * Asks for a captcha ar next authentication
     *
     * @return Application
     */
    public function requireCaptcha()
    {
        if ($this['phraseanet.registry']->get('GV_captchas')) {
            $this['session']->set('require_captcha', true);
        }

        return $this;
    }

    /**
     * Returns true if a captcha is required for next authentication
     *
     * @return boolean
     */
    public function isCaptchaRequired()
    {
        if ($this['session']->has('require_captcha')) {
            $this['session']->remove('require_captcha');

            return true;
        }

        return false;
    }

    /**
     * Returns true if guest access is allowed.
     *
     * @return boolean
     */
    public function isGuestAllowed()
    {
        $usrId = \User_Adapter::get_usr_id_from_login($this, 'invite');

        if (!$usrId) {
            return false;
        }

        return count($this['acl']->get(\User_Adapter::getInstance($usrId, $this))->get_granted_base()) > 0;
    }

    /**
     * Returns true if application has terms of use
     *
     * @return noolean
     */
    public function hasTermsOfUse()
    {
        return '' !== \databox_cgu::getHome($this);
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

    public function bindRoutes()
    {
        $this->mount('/', new Root());
        $this->mount('/feeds/', new RSSFeeds());
        $this->mount('/account/', new Account());
        $this->mount('/login/', new Login());
        $this->mount('/developers/', new Developers());

        $this->mount('/datafiles/', new Datafiles());

        $this->mount('/admin/', new AdminRoot());
        $this->mount('/admin/dashboard', new Dashboard());
        $this->mount('/admin/collection', new Collection());
        $this->mount('/admin/databox', new Databox());
        $this->mount('/admin/databoxes', new Databoxes());
        $this->mount('/admin/setup', new Setup());
        $this->mount('/admin/search-engine', new SearchEngine());
        $this->mount('/admin/connected-users', new ConnectedUsers());
        $this->mount('/admin/publications', new Publications());
        $this->mount('/admin/users', new Users());
        $this->mount('/admin/fields', new Fields());
        $this->mount('/admin/task-manager', new TaskManager());
        $this->mount('/admin/subdefs', new Subdefs());
        $this->mount('/admin/tests/connection', new ConnectionTest());
        $this->mount('/admin/tests/pathurl', new PathFileTest());

        $this->mount('/client/', new ClientRoot());
        $this->mount('/client/baskets', new ClientBasket());

        $this->mount('/prod/query/', new Query());
        $this->mount('/prod/order/', new Order());
        $this->mount('/prod/baskets', new BasketController());
        $this->mount('/prod/download', new Download());
        $this->mount('/prod/story', new Story());
        $this->mount('/prod/WorkZone', new WorkZone());
        $this->mount('/prod/lists', new UsrLists());
        $this->mount('/prod/records/', new Records());
        $this->mount('/prod/records/edit', new Edit());
        $this->mount('/prod/records/property', new Property());
        $this->mount('/prod/records/movecollection', new MoveCollection());
        $this->mount('/prod/bridge/', new Bridge());
        $this->mount('/prod/push/', new Push());
        $this->mount('/prod/printer/', new Printer());
        $this->mount('/prod/share/', new Share());
        $this->mount('/prod/export/', new Export());
        $this->mount('/prod/TOU/', new TOU());
        $this->mount('/prod/feeds', new Feed());
        $this->mount('/prod/tooltip', new Tooltip());
        $this->mount('/prod/language', new Language());
        $this->mount('/prod/tools/', new Tools());
        $this->mount('/prod/lazaret/', new Lazaret());
        $this->mount('/prod/upload/', new Upload());
        $this->mount('/prod/', new Prod());

        $this->mount('/user/preferences/', new Preferences());
        $this->mount('/user/notifications/', new Notifications());

        $this->mount('/download/', new DoDownload());
        $this->mount('/session/', new Session());

        $this->mount('/setup', new SetupController());
        $this->mount('/setup/connection_test/', new ConnectionTest());
        $this->mount('/setup/test/', new PathFileTest());

        $this->mount('/report/', new ReportRoot());
        $this->mount('/report/activity', new ReportActivity());
        $this->mount('/report/informations', new ReportInformations());
        $this->mount('/report/export', new ReportExport());

        $this->mount('/thesaurus', new Thesaurus());
        $this->mount('/xmlhttp', new ThesaurusXMLHttp());
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
     * Returns available flash message types for Phraseanet
     *
     * @return array
     */
    public static function getAvailableFlashTypes()
    {
        return static::$flashTypes;
    }
}
