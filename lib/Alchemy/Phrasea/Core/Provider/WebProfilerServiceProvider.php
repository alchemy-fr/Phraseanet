<?php

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bundle\FrameworkBundle\DataCollector\AjaxDataCollector;

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
        if (class_exists('Symfony\Bundle\FrameworkBundle\DataCollector\AjaxDataCollector')) {
            $app['data_collector.templates'] = $app->extend('data_collector.templates', function (array $templates) {
                $templates[] = array('ajax', '@WebProfiler/Collector/ajax.html.twig');

                return $templates;
            });

            $app['data_collectors'] = $app->extend('data_collectors', function ($collectors) {
                $collectors['ajax'] = function () {
                    return new AjaxDataCollector();
                };

                return $collectors;
            });
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
