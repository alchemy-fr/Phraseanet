<?php

namespace Alchemy\Phrasea\Core\Provider;

use RandomLib\Factory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class RandomGeneratorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['random.factory'] = $app->share(function (Application $app) {
            return new Factory();
        });

        $app['random.low'] = $app->share(function (Application $app) {
            return $app['random.factory']->getLowStrengthGenerator();
        });

        $app['random.medium'] = $app->share(function (Application $app) {
            return $app['random.factory']->getMediumStrengthGenerator();
        });

        $app['random.high'] = $app->share(function (Application $app) {
            return $app['random.factory']->getHighStrengthGenerator();
        });
    }

    public function boot(Application $app)
    {
    }
}
