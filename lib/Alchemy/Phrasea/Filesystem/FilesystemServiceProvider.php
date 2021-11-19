<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Filesystem;

use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Neutron\TemporaryFilesystem\Manager;
use Neutron\TemporaryFilesystem\TemporaryFilesystem;
use Silex\Application;
use Silex\ServiceProviderInterface;

//use Symfony\Component\Filesystem\Filesystem;

class FilesystemServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['filesystem'] = $app->share(function () {
            return new Filesystem();
        });

        $app['temporary-filesystem.temporary-fs'] = $app->share(function (Application $app) {
            return new TemporaryFilesystem($app['filesystem']);
        });
        $app['temporary-filesystem'] = $app->share(function (Application $app) {
            return new Manager($app['temporary-filesystem.temporary-fs'], $app['filesystem']);
        });

        $app['phraseanet.filesystem'] = $app->share(function (Application $app) {
            return new FilesystemService($app['filesystem']);
        });

        $app['phraseanet.lazaret_filesystem'] = $app->share(function (Application $app) {
            return new LazaretFilesystemService($app['filesystem'], $app['tmp.lazaret.path'], $app['media-alchemyst']);
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
