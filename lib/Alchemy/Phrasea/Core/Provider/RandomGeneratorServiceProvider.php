<?php

namespace Alchemy\Phrasea\Core\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RandomLib\Factory;
use Silex\Application;

class RandomGeneratorServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['random.factory'] = function (Application $app) {
            return new Factory();
        };

        $app['random.low'] = function (Application $app) {
            return $app['random.factory']->getLowStrengthGenerator();
        };

        $app['random.medium'] = function (Application $app) {
            return $app['random.factory']->getMediumStrengthGenerator();
        };

        $app['random.high'] = function (Application $app) {
            return $app['random.factory']->getHighStrengthGenerator();
        };
    }
}
