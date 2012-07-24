<?php

namespace Alchemy\Phrasea;

use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class PhraseanetServiceProvider implements ServiceProviderInterface
{

    public function register(SilexApplication $app)
    {
        $app['phraseanet.core'] = $app->share(function() {
            return \bootstrap::getCore();
        });

        $app['phraseanet.appbox'] = $app->share(function($app) {
            return new \appbox($app['phraseanet.core']);
        });
    }

    public function boot(SilexApplication $app)
    {

    }
}
