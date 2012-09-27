<?php

namespace Alchemy\Phrasea;

use Alchemy\Phrasea\Core\Version;
use Alchemy\Phrasea\Security\Firewall;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class PhraseanetServiceProvider implements ServiceProviderInterface
{

    public function register(SilexApplication $app)
    {
        $app['phraseanet.appbox'] = $app->share(function(SilexApplication $app) {
            return new \appbox($app);
        });

        $app['phraseanet.version'] = $app->share(function(SilexApplication $app) {
            return new Version();
        });

        $app['phraseanet.registry'] = $app->share(function(SilexApplication $app) {
            return new \registry($app);
        });

        $app['firewall'] = $app->share(function(SilexApplication $app) {
            return new Firewall($app);
        });

        $app['events-manager'] = $app->share(function(SilexApplication $app) {
            $events = new \eventsmanager_broker($app);
            $events->start();

            return $events;
        });
    }

    public function boot(SilexApplication $app)
    {
    }
}
