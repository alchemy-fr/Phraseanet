<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Core\Event\Subscriber\CacheStatisticsSubscriber;
use Alchemy\Phrasea\Core\Profiler\CacheDataCollector;
use Alchemy\Phrasea\Core\Profiler\TraceableCache;
use Alchemy\Phrasea\Utilities\LazyArrayAccess;
use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\DBAL\Logging\LoggerChain;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Sorien\DataCollector\DoctrineDataCollector;
use Sorien\Logger\DbalLogger;
use Symfony\Bundle\FrameworkBundle\DataCollector\AjaxDataCollector;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener;

class WebProfilerServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        // Required because the Silex provider is not up to date with Symfony Debug Toolbar
        $app['web_profiler.toolbar.listener'] = $app->share(
            $app->extend('web_profiler.toolbar.listener', function () use ($app) {
                return new WebDebugToolbarListener(
                    $app['twig'],
                    $app['web_profiler.debug_toolbar.intercept_redirects'],
                    2,
                    $app['web_profiler.debug_toolbar.position'],
                    $app['url_generator']
                );
            })
        );

        if (class_exists(AjaxDataCollector::class)) {
            $app['data_collector.templates'] = $app->share($app->extend('data_collector.templates', function (array $templates) {
                $templates[] = array('ajax', '@WebProfiler/Collector/ajax.html.twig');

                return $templates;
            }));

            $app['data_collectors'] = $app->share($app->extend('data_collectors', function (array $collectors, $app) {
                $collectors['ajax'] = $app->share(function () {
                    return new AjaxDataCollector();
                });

                return $collectors;
            }));
        }

        $app['dbal.config.register.loggers'] = $app->protect(function (Configuration $config, $name) use ($app) {
            $debugLogger = new DebugStack();

            $loggerChain = new LoggerChain();
            $loggerChain->addLogger($debugLogger);
            $loggerChain->addLogger($app['data_collectors.doctrine.logger']);

            $app['data_collectors.doctrine']->addLogger($name, $debugLogger);

            $config->setSQLLogger($loggerChain);
        });

        $app['data_collectors.doctrine.logger'] = $app->share(function ($app) {
            return new DbalLogger($app['logger'], $app['stopwatch']);
        });

        $app['data_collectors.doctrine'] = $app->share(function ($app) {
            return new DoctrineDataCollector(new LazyArrayAccess(new LazyLocator($app, 'dbs')));
        });

        $app['cache'] = $app->share($app->extend('cache', function (Cache $cache, $app) {
            $namespace = $app['conf']->get(['main', 'cache', 'options', 'namespace']);
            $cache = new TraceableCache($cache);

            $cache->setNamespace($namespace);

            return $cache;
        }));

        $app['data_collector.cache_subscriber'] = $app->share(function ($app) {
            return new CacheStatisticsSubscriber($app['cache']);
        });

        $app['data_collectors'] = $app->share($app->extend('data_collectors', function (array $collectors, $app) {
            $collectors['db'] = $app->share(function ($app) {
                return $app['data_collectors.doctrine'];
            });

            $collectors['cache'] = $app->share(function ($app) {
                return new CacheDataCollector($app['data_collector.cache_subscriber']);
            });

            return $collectors;
        }));

        $app['data_collector.templates'] = $app->share($app->extend('data_collector.templates', function (array $templates) {
            $templates[] = array('db', '@DoctrineBundle/Collector/db.html.twig');
            $templates[] = array('cache', '@PhraseaProfiler/cache.html.twig');

            return $templates;
        }));

        $app['twig.loader.filesystem'] = $app->share($app->extend('twig.loader.filesystem', function ($loader, $app) {
            $loader->addPath(
                $app['root.path'] . '/vendor/sorien/silex-dbal-profiler/src/Sorien/Resources/views',
                'DoctrineBundle'
            );
            $loader->addPath($app['root.path'] . '/templates-profiler/', 'PhraseaProfiler');
            return $loader;
        }));
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        $app['dispatcher']->addSubscriber($app['data_collector.cache_subscriber']);
    }
}
