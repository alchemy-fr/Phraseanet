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
use Alchemy\Phrasea\Core\Provider\CacheServiceProvider;
use Alchemy\Phrasea\Core\Provider\ConfigurationServiceProvider;
use Alchemy\Phrasea\Core\Provider\ORMServiceProvider;
use Monolog\Handler\NullHandler;
use Neutron\Silex\Provider\FilesystemServiceProvider;
use Silex\Application as SilexApplication;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class Setup extends SilexApplication
{
    private static $availableLanguages = array(
        'ar_SA'               => 'العربية'
        , 'de_DE'               => 'Deutsch'
        , 'en_GB'               => 'English'
        , 'es_ES'               => 'Español'
        , 'fr_FR'               => 'Français'
    );

    public function __construct()
    {
        parent::__construct();

        error_reporting(E_ALL | E_STRICT);

        ini_set('display_errors', 'on');
        ini_set('default_charset', 'UTF-8');
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.auto_start', '0');
        ini_set('session.hash_function', '1');
        ini_set('session.hash_bits_per_character', '6');
        ini_set('session.cache_limiter', '');
        mb_internal_encoding("UTF-8");

        $this['charset'] = 'UTF-8';

        $this['debug'] = true;

        ini_set('display_errors', 'on');
        ini_set('log_errors', 'on');
        ini_set('error_log', __DIR__ . '/../../../logs/php_error.log');

        $this->register(new CacheServiceProvider());
        $this->register(new ConfigurationServiceProvider());
        $this->register(new FilesystemServiceProvider());
        $this->register(new MonologServiceProvider());
        $this->register(new ORMServiceProvider());
        $this->register(new PhraseanetServiceProvider());
        $this->register(new SessionServiceProvider());
        $this->register(new UrlGeneratorServiceProvider());
        $this->register(new ValidatorServiceProvider());

        $this['monolog.name'] = 'Phraseanet logger';
        $this['monolog.handler'] = $this->share(function () {
            return new NullHandler();
        });

        $this->register(new TwigServiceProvider(), array(
            'twig.options' => array(
                'cache' => realpath(__DIR__ . '/../../../../../../tmp/cache_twig/'),
            )
        ));

        $this->setupTwig();

        $this['dispatcher']->addListener(KernelEvents::REQUEST, array($this, 'addLocale'), 255);
        $this['dispatcher']->addListener(KernelEvents::RESPONSE, array($this, 'addUTF8Charset'), -128);

        $this['locale'] = $this->share(function(Application $app) {
            return $app['phraseanet.registry']->get('GV_default_lng', 'en_GB');
        });

        $this['locale.I18n'] = function(Application $app) {
            $data = explode('_', $app['locale']);

            return $data[0];
        };

        $this['locale.l10n'] = function(Application $app) {
            $data = explode('_', $app['locale']);

            return $data[1];
        };
    }

    public function addUTF8Charset(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $event->setResponse($event->getResponse()->setCharset('UTF-8'));
    }

    public function addLocale(GetResponseEvent $event)
    {
        $this['locale'] = $this->share(function(Application $app) use ($event) {
            if ($event->getRequest()->cookies->has('locale')
                && isset(static::$availableLanguages[$event->getRequest()->cookies->get('locale')])) {
                $event->getRequest()->setLocale($event->getRequest()->cookies->get('locale'));

                return $event->getRequest()->getLocale();
            }

            $event->getRequest()->setDefaultLocale(
                $app['phraseanet.registry']->get('GV_default_lng', 'en_GB')
            );

            return $event->getRequest()->getLocale();
        });

        \phrasea::use_i18n($this['locale']);
    }

    public function setupTwig()
    {
        $this['twig'] = $this->share(
            $this->extend('twig', function ($twig, $app) {
                $app['twig.loader.filesystem']->setPaths(array(
                    realpath(__DIR__ . '/../../../config/templates/web'),
                    realpath(__DIR__ . '/../../../templates/web'),
                ));

                $twig->addGlobal('app', $app);

                $twig->addExtension(new \Twig_Extension_Core());
                $twig->addExtension(new \Twig_Extension_Optimizer());
                $twig->addExtension(new \Twig_Extension_Escaper());
                // add filter trans
                $twig->addExtension(new \Twig_Extensions_Extension_I18n());
                // add filter localizeddate
                $twig->addExtension(new \Twig_Extensions_Extension_Intl());
                // add filters truncate, wordwrap, nl2br
                $twig->addExtension(new \Twig_Extensions_Extension_Text());

                return $twig;
            })
        );
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
}
