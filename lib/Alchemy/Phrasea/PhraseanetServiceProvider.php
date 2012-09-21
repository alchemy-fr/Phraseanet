<?php

namespace Alchemy\Phrasea;

use Alchemy\Phrasea\Core\Version;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class PhraseanetServiceProvider implements ServiceProviderInterface
{

    public function register(SilexApplication $app)
    {
        $app['phraseanet.appbox'] = $app->share(function($app) {
            return new \appbox($app);
        });

        $app['phraseanet.version'] = $app->share(function($app) {
            return new Version();
        });
    }

    public function boot(SilexApplication $app)
    {

    }
}
