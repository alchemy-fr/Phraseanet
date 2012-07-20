<?php

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class BrowserServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['browser'] = $app->share(function($app) {
                return new \Browser();
            });
    }

    public function boot(Application $app)
    {

    }
}
