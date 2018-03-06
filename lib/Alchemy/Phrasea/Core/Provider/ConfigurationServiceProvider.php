<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\StructureTemplate;
use Alchemy\Phrasea\Core\Configuration\AccessRestriction;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Alchemy\Phrasea\Core\Configuration\DisplaySettingService;
use Alchemy\Phrasea\Core\Configuration\HostConfiguration;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Alchemy\Phrasea\Core\Configuration\RegistryManipulator;
use Alchemy\Phrasea\Core\Event\Subscriber\ConfigurationLoaderSubscriber;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;
use Symfony\Component\Yaml\Yaml;
use Alchemy\Phrasea\Core\Event\Subscriber\TrustedProxySubscriber;

class ConfigurationServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {
        $app['phraseanet.configuration.yaml-parser'] = $app->share(function (SilexApplication $app) {
            return new Yaml();
        });
        $app['phraseanet.configuration.compiler'] = $app->share(function (SilexApplication $app) {
            return new Compiler();
        });
        $app['phraseanet.configuration.config-path'] = function (SilexApplication $app) {
            return sprintf('%s/config/configuration.yml', $app['root.path']);
        };
        $app['phraseanet.configuration.config-compiled-path'] = function (SilexApplication $app) {
            return sprintf('%s/config/configuration-compiled.php', $app['root.path']);
        };

        $app['configuration.store'] = $app->share(function (SilexApplication $app) {
            return new HostConfiguration(new Configuration(
                $app['phraseanet.configuration.yaml-parser'],
                $app['phraseanet.configuration.compiler'],
                $app['phraseanet.configuration.config-path'],
                $app['phraseanet.configuration.config-compiled-path'],
                $app['debug']
            ));
        });

        $app['registry.manipulator'] = $app->share(function (SilexApplication $app) {
            return new RegistryManipulator($app['form.factory'], $app['translator'], $app['locales.available']);
        });

        $app['conf'] = $app->share(function (SilexApplication $app) {
            return new PropertyAccess($app['configuration.store']);
        });

        // Maintaining BC until 3.10
        $app['phraseanet.configuration'] = $app->share(function (SilexApplication $app) {
            return $app['configuration.store'];
        });

        $app['settings'] = $app->share(function (SilexApplication $app) {
            return new DisplaySettingService($app['conf']);
        });

        $app['conf.restrictions'] = $app->share(function (SilexApplication $app) {
            return new AccessRestriction($app['conf'], $app->getApplicationBox(), $app['monolog']);
        });

        $app['phraseanet.structure-template'] = $app->share(function (Application $app) {
            return new StructureTemplate($app['root.path']);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(SilexApplication $app)
    {
        $app['dispatcher'] = $app->share(
            $app->extend('dispatcher', function ($dispatcher, SilexApplication $app) {
                $dispatcher->addSubscriber(new ConfigurationLoaderSubscriber($app['configuration.store']));
                $dispatcher->addSubscriber(new TrustedProxySubscriber($app['configuration.store']));

                return $dispatcher;
            })
        );
    }
}
