<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea;

use Alchemy\Geonames\GeonamesServiceProvider;
use Alchemy\Phrasea\Application\Environment;
use Alchemy\Phrasea\Application\Helper\AclAware;
use Alchemy\Phrasea\Application\Helper\ApplicationBoxAware;
use Alchemy\Phrasea\Application\Helper\AuthenticatorAware;
use Alchemy\Phrasea\Application\RouteLoader;
use Alchemy\Phrasea\Authorization\AuthorizationServiceProvider;
use Alchemy\Phrasea\Core\Event\Subscriber\BasketSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\BridgeSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\ExportSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\FeedEntrySubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\LazaretSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaInstallSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\RegistrationSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\ValidationSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\WebhookUserEventSubscriber;
use Alchemy\Phrasea\Core\MetaProvider\DatabaseMetaProvider;
use Alchemy\Phrasea\Core\MetaProvider\HttpStackMetaProvider;
use Alchemy\Phrasea\Core\MetaProvider\MediaUtilitiesMetaServiceProvider;
use Alchemy\Phrasea\Core\MetaProvider\TemplateEngineMetaProvider;
use Alchemy\Phrasea\Core\MetaProvider\TranslationMetaProvider;
use Alchemy\Phrasea\Core\Middleware\ApiApplicationMiddlewareProvider;
use Alchemy\Phrasea\Core\Middleware\BasketMiddlewareProvider;
use Alchemy\Phrasea\Core\Middleware\TokenMiddlewareProvider;
use Alchemy\Phrasea\Core\PhraseaExceptionHandler;
use Alchemy\Phrasea\Core\Provider\AccountServiceProvider;
use Alchemy\Phrasea\Core\Provider\ACLServiceProvider;
use Alchemy\Phrasea\Core\Provider\APIServiceProvider;
use Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider;
use Alchemy\Phrasea\Core\Provider\BorderManagerServiceProvider;
use Alchemy\Phrasea\Core\Provider\BrowserServiceProvider;
use Alchemy\Phrasea\Core\Provider\CacheConnectionServiceProvider;
use Alchemy\Phrasea\Core\Provider\CacheServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConvertersServiceProvider;
use Alchemy\Phrasea\Core\Provider\CSVServiceProvider;
use Alchemy\Phrasea\Core\Provider\DataboxServiceProvider;
use Alchemy\Phrasea\Core\Provider\FeedServiceProvider;
use Alchemy\Phrasea\Core\Provider\FileServeServiceProvider;
use Alchemy\Phrasea\Core\Provider\FtpServiceProvider;
use Alchemy\Phrasea\Core\Provider\InstallerServiceProvider;
use Alchemy\Phrasea\Core\Provider\JMSSerializerServiceProvider;
use Alchemy\Phrasea\Core\Provider\LocaleServiceProvider;
use Alchemy\Phrasea\Core\Provider\ManipulatorServiceProvider;
use Alchemy\Phrasea\Core\Provider\NotificationDelivererServiceProvider;
use Alchemy\Phrasea\Core\Provider\OrderServiceProvider;
use Alchemy\Phrasea\Core\Provider\PhraseaEventServiceProvider;
use Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider;
use Alchemy\Phrasea\Core\Provider\PhraseaVersionServiceProvider;
use Alchemy\Phrasea\Core\Provider\PluginServiceProvider;
use Alchemy\Phrasea\Core\Provider\RandomGeneratorServiceProvider;
use Alchemy\Phrasea\Core\Provider\RegistrationServiceProvider;
use Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider;
use Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider;
use Alchemy\Phrasea\Core\Provider\SerializerServiceProvider;
use Alchemy\Phrasea\Core\Provider\StatusServiceProvider;
use Alchemy\Phrasea\Core\Provider\SubdefServiceProvider;
use Alchemy\Phrasea\Core\Provider\TasksServiceProvider;
use Alchemy\Phrasea\Core\Provider\TokensServiceProvider;
use Alchemy\Phrasea\Core\Provider\UnicodeServiceProvider;
use Alchemy\Phrasea\Core\Provider\WebhookServiceProvider;
use Alchemy\Phrasea\Core\Provider\ZippyServiceProvider;
use Alchemy\Phrasea\Core\Provider\WebProfilerServiceProvider as PhraseaWebProfilerServiceProvider;
use Alchemy\Phrasea\Databox\Caption\CaptionServiceProvider;
use Alchemy\Phrasea\Databox\Subdef\MediaSubdefServiceProvider;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Filesystem\FilesystemServiceProvider;
use Alchemy\Phrasea\Filesystem\ApplicationPathServiceGenerator;
use Alchemy\Phrasea\Form\Extension\HelpTypeExtension;
use Alchemy\Phrasea\Media\DatafilesResolver;
use Alchemy\Phrasea\Media\MediaAccessorResolver;
use Alchemy\Phrasea\Media\PermalinkMediaResolver;
use Alchemy\Phrasea\Media\TechnicalDataServiceProvider;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use MediaVorus\Media\MediaInterface;
use MediaVorus\MediaVorus;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Neutron\ReCaptcha\ReCaptchaServiceProvider;
use Silex\Application as SilexApplication;
use Silex\Application\TranslationTrait;
use Silex\Application\UrlGeneratorTrait;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;
use Sorien\Provider\PimpleDumpProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Exception\ExceptionInterface;
use Symfony\Component\Form\Exception\FormException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Unoconv\UnoconvServiceProvider;
use XPDF\PdfToText;
use XPDF\XPDFServiceProvider;

class Application extends SilexApplication
{
    use AclAware;
    use ApplicationBoxAware;
    use AuthenticatorAware;
    use UrlGeneratorTrait;
    use TranslationTrait;

    const ENV_DEV = 'dev';
    const ENV_PROD = 'prod';
    const ENV_TEST = 'test';

    protected static $availableLanguages = [
        'de' => 'Deutsch',
        'en' => 'English',
        'fr' => 'Français',
        'nl' => 'Dutch',
    ];

    private static $flashTypes = ['warning', 'info', 'success', 'error'];

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @param Environment|string $environment
     */
    public function __construct($environment = null)
    {
        if (is_string($environment)) {
            $environment = new Environment($environment, false);
        }

        $this->environment = $environment ?: new Environment(self::ENV_PROD, false);

        parent::__construct([
            'debug' => $this->environment->isDebug()
        ]);

        $this->setupCharset();
        $this->setupApplicationPaths();
        $this->setupConstants();

        if ('allowed' == getenv('APP_CONTAINER_DUMP')) {
            $this->register(new PimpleDumpProvider());
        }

        $this->register(new ConfigurationServiceProvider());
        $this->register(new MonologServiceProvider());
        $this->setupMonolog();
        $this->register(new FilesystemServiceProvider());
        $this->register(new CacheServiceProvider());
        $this->register(new CacheConnectionServiceProvider());
        $this->register(new PhraseanetServiceProvider());
        $this->register(new ConfigurationTesterServiceProvider());

        $this->register(new DatabaseMetaProvider());

        $this->register(new BasketMiddlewareProvider());
        $this->register(new TokenMiddlewareProvider());
        $this->register(new AccountServiceProvider());
        $this->register(new ApiApplicationMiddlewareProvider());
        $this->register(new ACLServiceProvider());
        $this->register(new APIServiceProvider());
        $this->register(new AuthenticationManagerServiceProvider());
        $this->register(new AuthorizationServiceProvider());
        $this->register(new BrowserServiceProvider());
        $this->register(new ConvertersServiceProvider());
        $this->register(new CSVServiceProvider());
        $this->register(new RegistrationServiceProvider());

        $this->register(new JMSSerializerServiceProvider());
        $this->register(new FeedServiceProvider());
        $this->register(new FtpServiceProvider());
        $this->register(new GeonamesServiceProvider());
        $this->register(new StatusServiceProvider());
        $this->setupGeonames();
        $this->register(new NotificationDelivererServiceProvider());
        $this->register(new RepositoriesServiceProvider());
        $this->register(new ManipulatorServiceProvider());
        $this->register(new TechnicalDataServiceProvider());
        $this->register(new MediaSubdefServiceProvider());
        $this->register(new CaptionServiceProvider());
        $this->register(new InstallerServiceProvider());
        $this->register(new PhraseaVersionServiceProvider());

        $this->register(new RandomGeneratorServiceProvider());
        $this->register(new ReCaptchaServiceProvider());
        $this->register(new SubdefServiceProvider());
        $this->register(new ZippyServiceProvider());
        $this->setupRecaptacha();

        if ($this['configuration.store']->isSetup()) {
            $this->register(new SearchEngineServiceProvider());
            $this->register(new BorderManagerServiceProvider());
        }


        $this->register(new SerializerServiceProvider());
        $this->register(new ServiceControllerServiceProvider());
        $this->register(new SwiftmailerServiceProvider());
        $this->setupSwiftMailer();
        $this->register(new TasksServiceProvider());
        $this->register(new TokensServiceProvider());

        $this->register(new TemplateEngineMetaProvider());
        $this->register(new HttpStackMetaProvider());
        $this->register(new MediaUtilitiesMetaServiceProvider());
        $this->register(new TranslationMetaProvider());

        $this->register(new FormServiceProvider());
        $this->setupForm();
        $this->register(new UnoconvServiceProvider());

        $this->register(new UnicodeServiceProvider());
        $this->register(new ValidatorServiceProvider());
        $this->register(new XPDFServiceProvider());
        $this->setupXpdf();
        $this->register(new FileServeServiceProvider());
        $this->register(new ManipulatorServiceProvider());
        $this->register(new PluginServiceProvider());
        $this->register(new PhraseaEventServiceProvider());

        $this->register(new LocaleServiceProvider());

        $this->setupEventDispatcher();

        $this->register(new DataboxServiceProvider());
        $this->register(new OrderServiceProvider());
        $this->register(new WebhookServiceProvider());

        $this['phraseanet.exception_handler'] = $this->share(function ($app) {
            /** @var PhraseaExceptionHandler $handler */
            $handler =  PhraseaExceptionHandler::register($app['debug']);

            $handler->setTranslator($app['translator']);
            $handler->setLogger($app['monolog']);

            return $handler;
        });

        $resolvers = $this['alchemy_embed.resource_resolvers'];
        $resolvers['datafile'] = $resolvers->share(function () {
            return new DatafilesResolver($this->getApplicationBox());
        });

        $resolvers['permalinks_permalink'] = $resolvers->share(function () {
            return new PermalinkMediaResolver($this->getApplicationBox());
        });

        $resolvers['media_accessor'] = $resolvers->share(function () {
            return new MediaAccessorResolver(
                $this->getApplicationBox(), $this['controller.media_accessor']
            );
        });

        if (self::ENV_DEV === $this->getEnvironment()) {
            $this->register($p = new WebProfilerServiceProvider(), [
                'profiler.cache_dir' => $this['cache.path'].'/profiler',
            ]);

            $this->register(new PhraseaWebProfilerServiceProvider());
            $this->mount('/_profiler', $p);

            if ($this['phraseanet.configuration-tester']->isInstalled()) {
                $this['db'] = $this->share(function (self $app) {
                    return $app['orm.em']->getConnection();
                });
            }
        }
    }

    public function getEnvironment()
    {
        return $this->environment->getName();
    }

    /**
     * Loads Phraseanet plugins
     */
    public function loadPlugins()
    {
        call_user_func(function ($app) {
            if (file_exists($app['plugin.path'] . '/services.php')) {
                require $app['plugin.path'] . '/services.php';
            }
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
     * @throws ExceptionInterface if any given option is not applicable to the given type
     */
    public function form($type = 'form', $data = null, array $options = [], FormBuilderInterface $parent = null)
    {
        return $this['form.factory']->create($type, $data, $options, $parent);
    }

    /**
     * Returns a redirect response with a relative path related to a route name.
     *
     * @param string $route      The name of the route
     * @param mixed  $parameters An array of parameters
     *
     * @return RedirectResponse
     */
    public function redirectPath($route, $parameters = [])
    {
        return $this->redirect($this->path($route, $parameters));
    }

    /**
     * Returns a redirect response with a fully qualified URI related to a route name.
     *
     * @param string $route      The name of the route
     * @param mixed  $parameters An array of parameters
     *
     * @return RedirectResponse
     */
    public function redirectUrl($route, $parameters = [])
    {
        return $this->redirect($this->url($route, $parameters));
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
    public function getFlash($type, array $default = [])
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
        if ($this['conf']->get(['registry', 'webservices', 'captcha-enabled'])) {
            $this['session']->set('require_captcha', true);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->environment->isDebug();
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
        if (null === $user = $this['repo.users']->findByLogin(User::USER_GUEST)) {
            return false;
        }

        return count($this->getAclForUser($user)->get_granted_base()) > 0;
    }

    /**
     * Returns true if application has terms of use
     *
     * @return bool
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
        return [];
    }

    /**
     * Mount all controllers
     */
    public function bindRoutes()
    {
        $loader = new RouteLoader();

        $loader->registerProviders(RouteLoader::$defaultProviders);

        $loader->bindRoutes($this);
        $this->bindPluginRoutes('plugin.controller_providers.root');
    }

    /**
     * Return available language for phraseanet
     *
     * @return array
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

    /**
     * Get Media instance given a file uri.
     *
     * @param string $uri
     *
     * @return MediaInterface
     */
    public function getMediaFromUri($uri)
    {
        /** @var MediaVorus $mediavorus */
        $mediavorus = $this['mediavorus'];

        return $mediavorus->guess($uri);
    }

    private function setupApplicationPaths()
    {
        // app root path
        $this['root.path'] = realpath(__DIR__ . '/../../..');
        // temporary resources default path such as download zip, quarantined documents etc ..
        $this['tmp.path'] = getenv('PHRASEANET_TMP') ?: $this['root.path'].'/tmp';

        // plugin path
        $this['plugin.path'] = $this['root.path'].'/plugins';
        // thumbnails path
        $this['thumbnail.path'] = $this['root.path'].'/www/thumbnails';

        $factory = new ApplicationPathServiceGenerator();

        $this['cache.path'] = $factory->createDefinition(
            ['main', 'storage', 'cache'],
            function (Application $app) {
                return $app['root.path'].'/cache';
            }
        );
        $this['cache.paths'] = function (Application $app) {
            return new \ArrayObject([
                $app['cache.path'],
            ]);
        };

        $this['log.path'] = $factory->createDefinition(
            ['main', 'storage', 'log'],
            function (Application $app) {
                return $app['root.path'].'/logs';
            }
        );

        $this['tmp.download.path'] = $factory->createDefinition(
            ['main', 'storage', 'download'],
            function (Application $app) {
                return $app['tmp.path'].'/download';
            }
        );

        $this['tmp.lazaret.path'] = $factory->createDefinition(
            ['main', 'storage', 'quarantine'],
            function (Application $app) {
                return $app['tmp.path'].'/lazaret';
            }
        );

        $this['tmp.caption.path'] = $factory->createDefinition(
            ['main', 'storage', 'caption'],
            function (Application $app) {
                return $app['tmp.path'].'/caption';
            }
        );
    }


    private function setupXpdf()
    {
        $this['xpdf.pdftotext'] = $this->share(
            $this->extend('xpdf.pdftotext', function (PdfToText $pdftotext, Application $app) {
                if ($app['conf']->get(['registry', 'executables', 'pdf-max-pages'])) {
                    $pdftotext->setPageQuantity($app['conf']->get(['registry', 'executables', 'pdf-max-pages']));
                }

                return $pdftotext;
            })
        );
    }

    private function setupForm()
    {
        $this['form.type.extensions'] = $this->share($this->extend('form.type.extensions', function ($extensions, Application $app) {
            $extensions[] = new HelpTypeExtension();

            return $extensions;
        }));
    }

    private function setupRecaptacha()
    {
        $this['recaptcha.public-key'] = $this->share(function (Application $app) {
            if ($app['conf']->get(['registry', 'webservices', 'captcha-enabled'])) {
                return $app['conf']->get(['registry', 'webservices', 'recaptcha-public-key']);
            }
        });
        $this['recaptcha.private-key'] = $this->share(function (Application $app) {
            if ($app['conf']->get(['registry', 'webservices', 'captcha-enabled'])) {
                return $app['conf']->get(['registry', 'webservices', 'recaptcha-private-key']);
            }
        });
    }

    private function setupGeonames()
    {
        $this['geonames.server-uri'] = $this->share(function (Application $app) {
            return $app['conf']->get(['registry', 'webservices', 'geonames-server'], 'http://geonames.alchemyasp.com/');
        });
    }

    private function setupSwiftMailer()
    {
        $this['swiftmailer.transport'] = $this->share(function (Application $app) {
            if ($app['conf']->get(['registry', 'email', 'smtp-enabled'])) {
                $transport = new \Swift_Transport_EsmtpTransport(
                    $app['swiftmailer.transport.buffer'],
                    [$app['swiftmailer.transport.authhandler']],
                    $app['swiftmailer.transport.eventdispatcher']
                );

                $encryption = null;

                if (in_array($app['conf']->get(['registry', 'email', 'smtp-secure-mode']), ['ssl', 'tls'])) {
                    $encryption = $app['conf']->get(['registry', 'email', 'smtp-secure-mode']);
                }

                $options = $app['swiftmailer.options'] = array_replace([
                    'host'       => $app['conf']->get(['registry', 'email', 'smtp-host']),
                    'port'       => $app['conf']->get(['registry', 'email', 'smtp-port']),
                    'username'   => $app['conf']->get(['registry', 'email', 'smtp-user']),
                    'password'   => $app['conf']->get(['registry', 'email', 'smtp-password']),
                    'encryption' => $encryption,
                    'auth_mode'  => null,
                ], $app['swiftmailer.options']);

                $transport->setHost($options['host']);
                $transport->setPort($options['port']);
                // tls or ssl
                $transport->setEncryption($options['encryption']);

                if ($app['conf']->get(['registry', 'email', 'smtp-auth-enabled'])) {
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
    }

    private function setupMonolog()
    {
        $this['monolog.name'] = 'phraseanet';
        $this['monolog.handler'] = $this->share(function (Application $app) {
            return new RotatingFileHandler(
                $app['log.path'] . '/app_error.log',
                10,
                Logger::ERROR,
                $app['monolog.bubble'],
                $app['monolog.permission']
            );
        });
    }

    private function setupEventDispatcher()
    {
        $this['dispatcher'] = $this->share(
            $this->extend('dispatcher', function (EventDispatcherInterface $dispatcher, Application $app) {
                $dispatcher->addSubscriber(new PhraseaInstallSubscriber($app));
                $dispatcher->addSubscriber(new FeedEntrySubscriber($app));
                $dispatcher->addSubscriber(new RegistrationSubscriber($app));
                $dispatcher->addSubscriber(new BridgeSubscriber($app));
                $dispatcher->addSubscriber(new ExportSubscriber($app));
                $dispatcher->addSubscriber(new BasketSubscriber($app));
                $dispatcher->addSubscriber(new LazaretSubscriber($app));
                $dispatcher->addSubscriber(new ValidationSubscriber($app));
                $dispatcher->addSubscriber(new WebhookUserEventSubscriber($app));

                return $dispatcher;
            })
        );
    }

    private function setupConstants()
    {
        if (!defined('JETON_MAKE_SUBDEF')) {
            define('JETON_MAKE_SUBDEF', 0x01);
        }

        if (!defined('JETON_WRITE_META_DOC')) {
            define('JETON_WRITE_META_DOC', 0x02);
        }

        if (!defined('JETON_WRITE_META_SUBDEF')) {
            define('JETON_WRITE_META_SUBDEF', 0x04);
        }

        if (!defined('JETON_WRITE_META')) {
            define('JETON_WRITE_META', 0x06);
        }
    }

    private function setupCharset()
    {
        $this['charset'] = 'UTF-8';
        mb_internal_encoding($this['charset']);
    }

    /**
     * @param $routeParameter
     */
    public function bindPluginRoutes($routeParameter)
    {
        $loader = new RouteLoader();

        $loader->bindPluginRoutes($this, $routeParameter);
    }
}
