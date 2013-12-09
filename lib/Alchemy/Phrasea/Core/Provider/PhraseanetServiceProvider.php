<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Security\Firewall;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class PhraseanetServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {
        $app['phraseanet.appbox'] = $app->share(function (SilexApplication $app) {
            return new \appbox($app);
        });

        $app['phraseanet.registry'] = $app->share(function (SilexApplication $app) {
            return new \registry($app);
        });

        $app['firewall'] = $app->share(function (SilexApplication $app) {
            return new Firewall($app);
        });

        $app['events-manager'] = $app->share(function (SilexApplication $app) {
            $events = new \eventsmanager_broker($app);
            $events->start();

            return $events;
        });

        $app['acl'] = $app->share(function (SilexApplication $app) {
            return new ACLProvider($app);
        });
    }

    public function boot(SilexApplication $app)
    {
    }
}
