<?php

namespace Alchemy\Phrasea;

use Alchemy\Phrasea\PhraseanetServiceProvider;
use Alchemy\Phrasea\Core\Provider\BrowserServiceProvider;
use Silex\Application as SilexApplication;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Application extends SilexApplication
{

    public function __construct()
    {
        parent::__construct();

        $this['charset'] = 'UTF-8';

        $this->register(new PhraseanetServiceProvider());

        $this['debug'] = $this['phraseanet.core']->getEnv() !== 'prod';

        $this->register(new ValidatorServiceProvider());
        $this->register(new UrlGeneratorServiceProvider());
        $this->register(new BrowserServiceProvider());

        $this->register(new TwigServiceProvider(), array(
            'twig.options' => array(
                'cache' => realpath(__DIR__ . '/../../../../../../tmp/cache_twig/'),
            )
        ));

        $this->setupTwig();

        $this->before(function(Request $request) {
            $request->setRequestFormat(
                $request->getFormat(
                    array_shift(
                        $request->getAcceptableContentTypes()
                    )
                )
            );
        });

//        $this->register(new \Silex\Provider\HttpCacheServiceProvider());
//        $this->register(new \Silex\Provider\MonologServiceProvider());
//        $this->register(new \Silex\Provider\SecurityServiceProvider());
//        $this->register(new \Silex\Provider\SessionServiceProvider());
//        $this->register(new \Silex\Provider\SwiftmailerServiceProvider());
//        $this->register(new \Silex\Provider\UrlGeneratorServiceProvider());
    }

    public function setupTwig()
    {

        $app = $this;
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

                    $twig->addGlobal('session', $app['phraseanet.appbox']->get_session());
                    $twig->addGlobal('appbox', $app['phraseanet.appbox']);
                    $twig->addGlobal('version_number', $app['phraseanet.core']->getVersion()->getNumber());
                    $twig->addGlobal('version_name', $app['phraseanet.core']->getVersion()->getName());
                    $twig->addGlobal('core', $app['phraseanet.core']);
                    $twig->addGlobal('browser', $app['browser']);
                    $twig->addGlobal('request', $app['request']);
                    $twig->addGlobal('events', \eventsmanager_broker::getInstance($app['phraseanet.appbox'], $app['phraseanet.core']));
                    $twig->addGlobal('display_chrome_frame', $app['phraseanet.appbox']->get_registry()->is_set('GV_display_gcf') ? $app['phraseanet.appbox']->get_registry()->get('GV_display_gcf') : true);
                    $twig->addGlobal('user', $app['phraseanet.core']->getAuthenticatedUser());
                    $twig->addGlobal('current_date', new \DateTime());
                    $twig->addGlobal('home_title', $app['phraseanet.appbox']->get_registry()->get('GV_homeTitle'));
                    $twig->addGlobal('meta_description', $app['phraseanet.appbox']->get_registry()->get('GV_metaDescription'));
                    $twig->addGlobal('meta_keywords', $app['phraseanet.appbox']->get_registry()->get('GV_metaKeywords'));
                    $twig->addGlobal('maintenance', $app['phraseanet.appbox']->get_registry()->get('GV_maintenance'));
                    $twig->addGlobal('registry', $app['phraseanet.appbox']->get_registry());

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

                    include_once __DIR__ . '/Twig/Functions.inc.php';

                    $twig->addTest('null', new \Twig_Test_Function('is_null'));
                    $twig->addTest('loopable', new \Twig_Test_Function('is_loopable'));

                    $twig->addFilter('serialize', new \Twig_Filter_Function('serialize'));
                    $twig->addFilter('stristr', new \Twig_Filter_Function('stristr'));
                    $twig->addFilter('implode', new \Twig_Filter_Function('implode'));
                    $twig->addFilter('get_class', new \Twig_Filter_Function('get_class'));
                    $twig->addFilter('stripdoublequotes', new \Twig_Filter_Function('stripdoublequotes'));
                    $twig->addFilter('geoname_display', new \Twig_Filter_Function('geonames::name_from_id'));
                    $twig->addFilter('get_collection_logo', new \Twig_Filter_Function('collection::getLogo'));
                    $twig->addFilter('floor', new \Twig_Filter_Function('floor'));
                    $twig->addFilter('bas_names', new \Twig_Filter_Function('phrasea::bas_names'));
                    $twig->addFilter('sbas_names', new \Twig_Filter_Function('phrasea::sbas_names'));
                    $twig->addFilter('urlencode', new \Twig_Filter_Function('urlencode'));
                    $twig->addFilter('sbasFromBas', new \Twig_Filter_Function('phrasea::sbasFromBas'));
                    $twig->addFilter('key_exists', new \Twig_Filter_Function('array_key_exists'));
                    $twig->addFilter('array_keys', new \Twig_Filter_Function('array_keys'));
                    $twig->addFilter('round', new \Twig_Filter_Function('round'));
                    $twig->addFilter('formatDate', new \Twig_Filter_Function('phraseadate::getDate'));
                    $twig->addFilter('prettyDate', new \Twig_Filter_Function('phraseadate::getPrettyString'));
                    $twig->addFilter('formatOctets', new \Twig_Filter_Function('p4string::format_octets'));
                    $twig->addFilter('geoname_name_from_id', new \Twig_Filter_Function('geonames::name_from_id'));
                    $twig->addFilter('base_from_coll', new \Twig_Filter_Function('phrasea::baseFromColl'));
                    $twig->addFilter('AppName', new \Twig_Filter_Function('Alchemy\Phrasea\Controller\Admin\ConnectedUsers::appName'));

                    return $twig;
                }));
    }

    public function run(Request $request = null)
    {
        $app = $this;

        $this->error(function($e) use ($app) {
            if ($app['debug']) {
                return new Response($e->getMessage(), 500);
            } else {
                return new Response(_('An error occured'), 500);
            }
        });
        parent::run($request);
    }
}

