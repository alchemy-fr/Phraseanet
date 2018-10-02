<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Alchemy\Phrasea\Setup\Installer;

class InstallerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['phraseanet.installer'] = $app->share(function (Application $app) {
            return new Installer($app);
        });
    }

    public function boot(Application $app)
    {
    }
}
