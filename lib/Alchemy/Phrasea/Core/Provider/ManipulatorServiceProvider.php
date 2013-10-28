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

use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Manager\UserManager;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ManipulatorServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['manipulator.user'] = $app->share(function($app) {
            return new UserManipulator($app['model.user-manager'], $app['auth.password-encoder'], $app['geonames.connector']);
        });

        $app['model.user-manager'] = $app->share(function($app) {
            return new UserManager($app['EM'], $app['phraseanet.appbox']->get_connection());
        });
    }

    public function boot(Application $app)
    {
    }
}
