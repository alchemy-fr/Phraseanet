<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider;

use Alchemy\Phrasea\Controller\MinifierController;
use Alchemy\Phrasea\Filesystem\PhraseanetFilesystem as Filesystem;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

// use Symfony\Component\Filesystem\Filesystem;

class Minifier implements ControllerProviderInterface, ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['controller.minifier'] = $app->share(function (Application $app) {
            $cachePath = $app['cache.path'] . '/minify';
            /** @var Filesystem $fs */
            $fs = $app['filesystem'];
            // ensure cache path created
            $fs->mkdir($cachePath);

            return new MinifierController($cachePath, $app['debug']);
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'controller.minifier:minifyAction')->bind('minifier');

        return $controllers;
    }
}
