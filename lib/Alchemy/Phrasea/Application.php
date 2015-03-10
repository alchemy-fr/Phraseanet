<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
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
use Alchemy\Phrasea\Controller\User\Notifications;
use Alchemy\Phrasea\Controller\User\Preferences;
use Alchemy\Phrasea\Core\Event\Subscriber\BasketSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\BridgeSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\ExportSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\FeedEntrySubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\LazaretSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\OrderSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\RegistrationSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\ValidationSubscriber;
use Alchemy\Phrasea\Core\Middleware\TokenMiddlewareProvider;
use Alchemy\Phrasea\Core\PhraseaExceptionHandler;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaInstallSubscriber;
use Alchemy\Phrasea\Core\Middleware\ApiApplicationMiddlewareProvider;
use Alchemy\Phrasea\Core\Middleware\BasketMiddlewareProvider;
use Alchemy\Phrasea\Core\Provider\ACLServiceProvider;
use Alchemy\Phrasea\Core\Provider\APIServiceProvider;
use Alchemy\Phrasea\Core\Provider\AuthenticationManagerServiceProvider;
use Alchemy\Phrasea\Core\Provider\BrowserServiceProvider;
use Alchemy\Phrasea\Core\Provider\BorderManagerServiceProvider;
use Alchemy\Phrasea\Core\Provider\CacheServiceProvider;
use Alchemy\Phrasea\Core\Provider\CacheConnectionServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationTesterServiceProvider;
use Alchemy\Phrasea\Core\Provider\ContentNegotiationServiceProvider;
use Alchemy\Phrasea\Core\Provider\CSVServiceProvider;
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
use Alchemy\Phrasea\Core\Provider\PhraseaEventServiceProvider;
use Alchemy\Phrasea\Core\Provider\PhraseanetServiceProvider;
use Alchemy\Phrasea\Core\Provider\PluginServiceProvider;
use Alchemy\Phrasea\Core\Provider\PhraseaVersionServiceProvider;
use Alchemy\Phrasea\Core\Provider\RandomGeneratorServiceProvider;
use Alchemy\Phrasea\Core\Provider\RegistrationServiceProvider;
use Alchemy\Phrasea\Core\Provider\RepositoriesServiceProvider;
use Alchemy\Phrasea\Core\Provider\SearchEngineServiceProvider;
use Alchemy\Phrasea\Core\Provider\SerializerServiceProvider;
use Alchemy\Phrasea\Core\Provider\SessionHandlerServiceProvider;
use Alchemy\Phrasea\Core\Provider\StatusServiceProvider;
use Alchemy\Phrasea\Core\Provider\SubdefServiceProvider;
use Alchemy\Phrasea\Core\Provider\TasksServiceProvider;
use Alchemy\Phrasea\Core\Provider\TemporaryFilesystemServiceProvider;
use Alchemy\Phrasea\Core\Provider\TokensServiceProvider;
use Alchemy\Phrasea\Core\Provider\TranslationServiceProvider;
use Alchemy\Phrasea\Core\Provider\UnicodeServiceProvider;
use Alchemy\Phrasea\Core\Provider\ZippyServiceProvider;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Form\Extension\HelpTypeExtension;
use Alchemy\Phrasea\Twig\JSUniqueID;
use Alchemy\Phrasea\Twig\Fit;
use Alchemy\Phrasea\Twig\Camelize;
use Alchemy\Phrasea\Twig\BytesConverter;
use Alchemy\Phrasea\Twig\PhraseanetExtension;
use Alchemy\Phrasea\Utilities\CachedTranslator;
use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use FFMpeg\FFMpegServiceProvider;
use Gedmo\DoctrineExtensions as GedmoExtension;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Neutron\Silex\Provider\ImagineServiceProvider;
use MediaVorus\MediaVorusServiceProvider;
use MediaAlchemyst\MediaAlchemystServiceProvider;
use Monolog\Handler\NullHandler;
use MP4Box\MP4BoxServiceProvider;
use Neutron\Silex\Provider\FilesystemServiceProvider;
use Neutron\ReCaptcha\ReCaptchaServiceProvider;
use PHPExiftool\PHPExiftoolServiceProvider;
use Silex\Application as SilexApplication;
use Silex\Application\UrlGeneratorTrait;
use Silex\Application\TranslationTrait;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\SwiftmailerServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Unoconv\UnoconvServiceProvider;
use XPDF\PdfToText;
use XPDF\XPDFServiceProvider;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Exception\FormException;

class Application extends SilexApplication
{
    use UrlGeneratorTrait;
    use TranslationTrait;

    protected static $availableLanguages = [
        'de' => 'Deutsch',
        'en' => 'English',
        'fr' => 'Français',
        'nl' => 'Dutch',
    ];

    private static $flashTypes = ['warning', 'info', 'success', 'error'];
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

        $this->environment = $environment;

        $this->setupCharset();
        $this->setupApplicationPaths();
        $this->setupConstants();

        $this['debug'] = $this->share(function (Application $app) {
            return Application::ENV_PROD !== $app->getEnvironment();
        });

        if ($this['debug']) {
            ini_set('log_errors', 'on');
            ini_set('error_log', $this['root.path'].'/logs/php_error.log');
        }

        $this->register(new ConfigurationServiceProvider());
        $this->register(new MonologServiceProvider());
        $this->setupMonolog();
        $this->register(new FilesystemServiceProvider());
        $this->register(new CacheServiceProvider());
        $this->register(new CacheConnectionServiceProvider());
        $this->register(new PhraseanetServiceProvider());
        $this->register(new ConfigurationTesterServiceProvider());
        $this->register(new ORMServiceProvider());
        $this->register(new DoctrineServiceProvider(), $this['dbs.service.conf']);
        $this->setupDBAL();
        $this->register(new DoctrineOrmServiceProvider(), $this['orm.service.conf']);
        $this->setupOrms();
        $this->register(new BasketMiddlewareProvider());
        $this->register(new TokenMiddlewareProvider());
        $this->register(new ApiApplicationMiddlewareProvider());
        $this->register(new ACLServiceProvider());
        $this->register(new APIServiceProvider());
        $this->register(new AuthenticationManagerServiceProvider());
        $this->register(new BrowserServiceProvider());
        $this->register(new ConvertersServiceProvider());
        $this->register(new CSVServiceProvider());
        $this->register(new RegistrationServiceProvider());
        $this->register(new ImagineServiceProvider());
        $this->setUpImagine();
        $this->register(new JMSSerializerServiceProvider());
        $this->register(new FFMpegServiceProvider());
        $this->register(new FeedServiceProvider());
        $this->register(new FtpServiceProvider());
        $this->register(new GeonamesServiceProvider());
        $this->register(new StatusServiceProvider());
        $this->setupGeonames();
        $this->register(new MediaAlchemystServiceProvider());
        $this->setupMediaAlchemyst();
        $this->register(new MediaVorusServiceProvider());
        $this->register(new MP4BoxServiceProvider());
        $this->register(new NotificationDelivererServiceProvider());
        $this->register(new RepositoriesServiceProvider());
        $this->register(new ManipulatorServiceProvider());
        $this->register(new InstallerServiceProvider());
        $this->register(new PhraseaVersionServiceProvider());
        $this->register(new PHPExiftoolServiceProvider());
        $this->register(new RandomGeneratorServiceProvider());
        $this->register(new ReCaptchaServiceProvider());
        $this->register(new SubdefServiceProvider());
        $this->register(new ZippyServiceProvider());
        $this->setupRecaptacha();

        if ($this['configuration.store']->isSetup()) {
            $this->register(new SearchEngineServiceProvider());
            $this->register(new BorderManagerServiceProvider());
        }

        $this->register(new SessionHandlerServiceProvider());
        $this->register(new SessionServiceProvider(), [
            'session.test' => $this->getEnvironment() === static::ENV_TEST,
            'session.storage.options' => ['cookie_lifetime' => 0]
        ]);
        $this->setupSession();
        $this->register(new SerializerServiceProvider());
        $this->register(new ServiceControllerServiceProvider());
        $this->register(new SwiftmailerServiceProvider());
        $this->setupSwiftMailer();
        $this->register(new TasksServiceProvider());
        $this->register(new TemporaryFilesystemServiceProvider());
        $this->register(new TokensServiceProvider());
        $this->register(new TwigServiceProvider(), [
            'twig.options' => [
                'cache' => $this->share(function($app) {return $app['cache.path'].'/twig';}),
            ],
        ]);
        $this->setupTwig();
        $this->register(new TranslationServiceProvider(), [
            'locale_fallbacks' => ['fr'],
            'translator.cache-options' => [
                'debug' => $this['debug'],
                'cache_dir' => $this->share(function($app) {
                    return $app['cache.path'].'/translations';
                }),
            ],
        ]);
        $this->setupTranslation();
        $this->register(new FormServiceProvider());
        $this->setupForm();
        $this->register(new UnoconvServiceProvider());
        $this->register(new UrlGeneratorServiceProvider());
        $this->setupUrlGenerator();
        $this->register(new UnicodeServiceProvider());
        $this->register(new ValidatorServiceProvider());
        $this->register(new XPDFServiceProvider());
        $this->setupXpdf();
        $this->register(new FileServeServiceProvider());
        $this->register(new ManipulatorServiceProvider());
        $this->register(new PluginServiceProvider());
        $this->register(new PhraseaEventServiceProvider());
        $this->register(new ContentNegotiationServiceProvider());
        $this->register(new LocaleServiceProvider());
        $this->setupEventDispatcher();
        $this['phraseanet.exception_handler'] = $this->share(function ($app) {
            $handler =  PhraseaExceptionHandler::register($app['debug']);
            $handler->setTranslator($app['translator']);

            return $handler;
        });
    }

    /**
     * Loads Phraseanet plugins
     */
    public function loadPlugins()
    {
        call_user_func(function ($app) {
            require $app['plugin.path'] . '/services.php';
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

    public function setupTwig()
    {
        $this['twig'] = $this->share(
            $this->extend('twig', function ($twig, $app) {
                $twig->setCache($app['cache.path'].'/twig');

                $paths = require $app['plugin.path'] . '/twig-paths.php';

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
                $twig->addExtension(new TranslationExtension($app['translator']));
                // add filter localizeddate
                $twig->addExtension(new \Twig_Extensions_Extension_Intl());
                // add filters truncate, wordwrap, nl2br
                $twig->addExtension(new \Twig_Extensions_Extension_Text());
                $twig->addExtension(new JSUniqueID());
                $twig->addExtension(new Fit());
                $twig->addExtension(new Camelize());
                $twig->addExtension(new BytesConverter());
                $twig->addExtension(new PhraseanetExtension($app));

                $twig->addFilter('serialize', new \Twig_Filter_Function('serialize'));
                $twig->addFilter('stristr', new \Twig_Filter_Function('stristr'));
                $twig->addFilter('get_class', new \Twig_Filter_Function('get_class'));
                $twig->addFilter('stripdoublequotes', new \Twig_Filter_Function('stripdoublequotes'));
                $twig->addFilter('get_collection_logo', new \Twig_Filter_Function('collection::getLogo'));
                $twig->addFilter('floor', new \Twig_Filter_Function('floor'));
                $twig->addFilter('ceil', new \Twig_Filter_Function('ceil'));
                $twig->addFilter('max', new \Twig_Filter_Function('max'));
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
                $twig->addFilter(new \Twig_SimpleFilter('AppName', function ($value) use ($app) {
                    return ConnectedUsers::appName($app['translator'], $value);
                }));
                $twig->addFilter(new \Twig_SimpleFilter('escapeSimpleQuote', function ($value) {
                    return str_replace("'", "\\'", $value);
                }));

                $twig->addFilter(new \Twig_SimpleFilter('highlight', function (\Twig_Environment $twig, $string) {
                    return str_replace(['[[em]]', '[[/em]]'], ['<em>', '</em>'], $string);
                }, ['needs_environment' => true,'is_safe' => ['html']]));

                $twig->addFilter(new \Twig_SimpleFilter('linkify', function (\Twig_Environment $twig, $string) {
                    return preg_replace(
                        "(([^']{1})((https?|file):((/{2,4})|(\\{2,4}))[\w:#%/;$()~_?/\-=\\\.&]*)([^']{1}))"
                        , '$1 $2 <a title="' . _('Open the URL in a new window') . '" class="ui-icon ui-icon-extlink" href="$2" style="display:inline;padding:2px 5px;margin:0 4px 0 2px;" target="_blank"> &nbsp;</a>$7'
                        , $string
                    );
                }, ['needs_environment' => true, 'is_safe' => ['html']]));

                $twig->addFilter(new \Twig_SimpleFilter('bounce', function (\Twig_Environment $twig, $fieldValue, $fieldName, $searchRequest, $sbasId) {
                        // bounce value if it is present in thesaurus as well
                    return "<a class=\"bounce\" onclick=\"bounce('"  .$sbasId . "','"
                            . str_replace("'", "\\'",$searchRequest)
                            . "', '"
                            . str_replace("'", "\\'", $fieldName)
                            . "');return(false);\">"
                            . $fieldValue
                            . "</a>";

                }, ['needs_environment' => true, 'is_safe' => ['html']]));

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

        return count($this['acl']->get($user)->get_granted_base()) > 0;
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
        return [];
    }

    /**
     * Mount all controllers
     */
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

        $this->mount('/report/', new ReportRoot());
        $this->mount('/report/activity', new ReportActivity());
        $this->mount('/report/informations', new ReportInformations());

        $this->mount('/thesaurus', new Thesaurus());
        $this->mount('/xmlhttp', new ThesaurusXMLHttp());

        $this->mount('/include/minify/', new Minifier());
        $this->mount('/permalink/', new Permalink());

        $this->mount('/lightbox/', new Lightbox());
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

    private function setupApplicationPaths()
    {
        // app root path
        $this['root.path'] = realpath(__DIR__ . '/../../..');
        // temporary resources default path such as download zip, quarantined documents etc ..
        $this['tmp.path'] = $this['root.path'].'/tmp';
        // plugin path
        $this['plugin.path'] = $dir = $this['root.path'].'/plugins';
        // thumbnails path
        $this['thumbnail.path'] = $dir = $this['root.path'].'/www/thumbnails';

        // cache path for dev env
        $this['cache.dev.path'] = $this->share(function() {
            $path =  sys_get_temp_dir().'/'.md5($this['root.path']);
            // ensure path is created
            $this['filesystem']->mkdir($path);

            return $path;
        });

        // cache path (twig, minify, translations, configuration, doctrine metas serializer metas, profiler etc ...)
        $this['cache.path'] = $this->share(function() {
//            if ($this->getEnvironment() !== Application::ENV_PROD) {
//                return $this['cache.dev.path'];
//            }
            $defaultPath = $path = $this['root.path'].'/cache';
            if ($this['phraseanet.configuration']->isSetup()) {
                $path = $this['conf']->get(['main', 'storage', 'cache'], $path);
            }
            $path = $path ?: $defaultPath;

            // ensure path is created
            $this['filesystem']->mkdir($path);

            return $path;
        });

        $this['cache.paths'] = $this->share(function() {
            return array(
                self::ENV_DEV => $this['cache.path'],
                self::ENV_TEST => $this['cache.path'],
                self::ENV_PROD => $this['cache.path']
            );
        });

        // log path
        $this['log.path'] = $this->share(function() {
            $defaultPath = $path = $this['root.path'].'/logs';
            if ($this['phraseanet.configuration']->isSetup()) {
                return $this['conf']->get(['main', 'storage', 'log'], $path);
            }
            $path = $path ?: $defaultPath;

            // ensure path is created
            $this['filesystem']->mkdir($path);

            return $path;
        });

        // temporary download file path (zip file)
        $this['tmp.download.path'] = $this->share(function() {
            $defaultPath = $path = $this['tmp.path'].'/download';
            if ($this['phraseanet.configuration']->isSetup()) {
                return $this['conf']->get(['main', 'storage', 'download'], $path);
            }
            $path = $path ?: $defaultPath;

            // ensure path is created
            $this['filesystem']->mkdir($path);

            return $path;
        });

        // quarantined file path
        $this['tmp.lazaret.path'] = $this->share(function() {
            $defaultPath = $path = $this['tmp.path'].'/lazaret';
            if ($this['phraseanet.configuration']->isSetup()) {
                return $this['conf']->get(['main', 'storage', 'quarantine'], $path);
            }
            $path = $path ?: $defaultPath;

            // ensure path is created
            $this['filesystem']->mkdir($path);

            return $path;
        });

        // document caption file path
        $this['tmp.caption.path'] = $this->share(function() {
            $defaultPath = $path = $this['tmp.path'].'/caption';
            if ($this['phraseanet.configuration']->isSetup()) {
                return $this['conf']->get(['main', 'storage', 'caption'], $path);
            }
            $path = $path ?: $defaultPath;

            // ensure path is created
            $this['filesystem']->mkdir($path);

            return $path;
        });
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

    private function setUpImagine()
    {
        $this['imagine.factory'] = $this->share(function (Application $app) {
            if ($app['conf']->get(['registry', 'executables', 'imagine-driver']) != '') {
                return $app['conf']->get(['registry', 'executables', 'imagine-driver']);
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
    }

    private function setupTranslation()
    {
        $this['translator'] = $this->share($this->extend('translator', function (CachedTranslator $translator, Application $app) {
            $translator->addResource('xlf', __DIR__.'/../../../resources/locales/messages.fr.xlf', 'fr', 'messages');
            $translator->addResource('xlf', __DIR__.'/../../../resources/locales/validators.fr.xlf', 'fr', 'validators');
            $translator->addResource('xlf', __DIR__.'/../../../resources/locales/messages.en.xlf', 'en', 'messages');
            $translator->addResource('xlf', __DIR__.'/../../../resources/locales/validators.en.xlf', 'en', 'validators');
            $translator->addResource('xlf', __DIR__.'/../../../resources/locales/messages.de.xlf', 'de', 'messages');
            $translator->addResource('xlf', __DIR__.'/../../../resources/locales/validators.de.xlf', 'de', 'validators');
            $translator->addResource('xlf', __DIR__.'/../../../resources/locales/messages.nl.xlf', 'nl', 'messages');
            $translator->addResource('xlf', __DIR__.'/../../../resources/locales/validators.nl.xlf', 'nl', 'validators');

            return $translator;
        }));
    }

    private function setupOrms()
    {
        $this['orm.ems'] = $this->share($this->extend('orm.ems', function ($ems, $app) {
            GedmoExtension::registerAnnotations();

            foreach ($ems->keys() as $key) {
                $app['orm.annotation.register']($key);
                $connection = $ems[$key]->getConnection();

                $app['connection.pool.manager']->add($connection);

                $types = $app['orm.ems.options'][$key]['types'];
                $app['dbal.type.register']($connection, $types);
            }

            return $ems;
        }));
    }

    private function setupSession()
    {
        $this['session.storage.test'] = $this->share(function (Application $app) {
            return new MockArraySessionStorage();
        });

        $this['session.storage.handler'] = $this->share(function (Application $app) {
            if (!$this['phraseanet.configuration-tester']->isInstalled()) {
                return new NullSessionHandler();
            }
            return $this['session.storage.handler.factory']->create($app['conf']);
        });
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

    private function setupDBAL()
    {
        $this['dbs.config'] = $this->share($this->extend('dbs.config', function ($configs, $app) {
            if ($app->getEnvironment() !== self::ENV_DEV) {
                return $configs;
            }

            foreach($configs->keys() as $service) {
                $app['dbal.config.register.loggers']($configs[$service]);
            }

            return $configs;
        }));

        $this['dbs.event_manager'] = $this->share($this->extend('dbs.event_manager', function ($eventManagers, $app) {
            foreach ($eventManagers->keys() as $name) {
                $app['dbal.evm.register.listeners']($eventManagers[$name]);
            }

            return $eventManagers;
        }));
    }

    private function setupMediaAlchemyst()
    {
        $this['media-alchemyst.configuration'] = $this->share(function (Application $app) {
            $configuration = [];

            foreach ([
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
                     ] as $parameter => $key) {
                if ($this['conf']->has(['main', 'binaries', $key])) {
                    $configuration[$parameter] = $this['conf']->get(['main', 'binaries', $key]);
                }
            }

            $configuration['ffmpeg.threads'] = $app['conf']->get(['registry', 'executables', 'ffmpeg-threads']) ?: null;
            $configuration['imagine.driver'] = $app['conf']->get(['registry', 'executables', 'imagine-driver']) ?: null;

            return $configuration;
        });
        $this['media-alchemyst.logger'] = $this->share(function (Application $app) {
            return $app['monolog'];
        });
    }

    private function setupUrlGenerator()
    {
        $this['url_generator'] = $this->share($this->extend('url_generator', function ($urlGenerator, Application $app) {
            if ($app['configuration.store']->isSetup()) {
                $data = parse_url($app['conf']->get('servername'));

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
        $this['monolog.handler'] = $this->share(function () {
            return new NullHandler();
        });
        $this['monolog'] = $this->share($this->extend('monolog', function (Logger $logger) {
            $logger->pushProcessor(new IntrospectionProcessor());

            return $logger;
        }));
    }

    private function setupEventDispatcher()
    {
        $this['dispatcher'] = $this->share(
            $this->extend('dispatcher', function ($dispatcher, Application $app) {
                //$dispatcher->addListener(KernelEvents::RESPONSE, [$app, 'addUTF8Charset'], -128);
                $dispatcher->addSubscriber($app['phraseanet.logout-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.locale-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.content-negotiation-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.maintenance-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.cookie-disabler-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.session-manager-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.record-edit-subscriber']);
                $dispatcher->addSubscriber(new PhraseaInstallSubscriber($app));
                $dispatcher->addSubscriber(new FeedEntrySubscriber($app));
                $dispatcher->addSubscriber(new RegistrationSubscriber($app));
                $dispatcher->addSubscriber(new BridgeSubscriber($app));
                $dispatcher->addSubscriber(new ExportSubscriber($app));
                $dispatcher->addSubscriber(new OrderSubscriber($app));
                $dispatcher->addSubscriber(new BasketSubscriber($app));
                $dispatcher->addSubscriber(new LazaretSubscriber($app));
                $dispatcher->addSubscriber(new ValidationSubscriber($app));

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
}
