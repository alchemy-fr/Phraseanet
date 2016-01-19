<?php

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
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

        if (class_exists('Symfony\Bundle\FrameworkBundle\DataCollector\AjaxDataCollector')) {
            $app['data_collector.templates'] = $app->share($app->extend('data_collector.templates', function (array $templates) {
                $templates[] = array('ajax', '@WebProfiler/Collector/ajax.html.twig');

                return $templates;
            }));

            $app['data_collectors'] = $app->share($app->extend('data_collectors', function ($collectors) {
                $collectors['ajax'] = function () {
                    return new AjaxDataCollector();
                };

                return $collectors;
            }));
        }
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
        // TODO: Implement boot() method.
    }
}
