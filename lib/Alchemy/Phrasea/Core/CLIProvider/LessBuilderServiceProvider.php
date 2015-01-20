<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\CLIProvider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Alchemy\Phrasea\Utilities\Less\Builder as LessBuilder;
use Alchemy\Phrasea\Utilities\Less\Compiler as LessCompiler;

class LessBuilderServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['phraseanet.less-assets'] = $app->share(function ($app) {
            return array(
                $app['root.path'] . '/www/assets/bootstrap/img/glyphicons-halflings-white.png' => $app['root.path'] . '/www/skins/build/bootstrap/img/glyphicons-halflings-white.png',
                $app['root.path'] . '/www/assets/bootstrap/img/glyphicons-halflings.png' => $app['root.path'] . '/www/skins/build/bootstrap/img/glyphicons-halflings.png',
            );
        });

        $app['phraseanet.less-mapping.base'] = $app->share(function ($app) {
            return array(
                $app['root.path'] . '/www/assets/bootstrap/less/bootstrap.less' => $app['root.path'] . '/www/skins/build/bootstrap/css/bootstrap.css',
                $app['root.path'] . '/www/assets/bootstrap/less/responsive.less' => $app['root.path'] . '/www/skins/build/bootstrap/css/bootstrap-responsive.css',
            );
        });

        $app['phraseanet.less-mapping.customizable'] = $app->share(function ($app) {
            return array(
                $app['root.path'] . '/www/skins/login/less/login.less' => $app['root.path'] . '/www/skins/build/login.css',
                $app['root.path'] . '/www/skins/account/account.less' => $app['root.path'] . '/www/skins/build/account.css',
            );
        });

        $app['phraseanet.less-mapping'] = $app->share(function ($app) {
            return array_merge(
                $app['phraseanet.less-mapping.base'],
                $app['phraseanet.less-mapping.customizable']
            );
        });

        $app['phraseanet.less-compiler'] = $app->share(function ($app) {
            return new LessCompiler($app['filesystem'], $app['driver.recess']);
        });

        $app['phraseanet.less-builder'] = $app->share(function ($app) {
            return new LessBuilder($app['phraseanet.less-compiler'], $app['filesystem']);
        });
    }

    public function boot(Application $app)
    {
    }
}
