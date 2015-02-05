<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Neutron\TemporaryFilesystem\TemporaryFilesystem;
use Neutron\TemporaryFilesystem\Manager;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TemporaryFilesystemServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['temporary-filesystem.temporary-fs'] = $app->share(function (Application $app) {
            return new TemporaryFilesystem($app['filesystem']);
        });
        $app['temporary-filesystem'] = $app->share(function (Application $app) {
            return new Manager($app['temporary-filesystem.temporary-fs'], $app['filesystem']);
        });
    }

    public function boot(Application $app)
    {
    }
}
