<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class PluginServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
    }

    public function boot(Application $app)
    {
        $app['twig'] = $app->share(
            $app->extend('twig', function ($twig, Application $app) {
                $function = new \Twig_SimpleFunction('plugin_asset', ['Alchemy\Phrasea\Plugin\Management\AssetsManager', 'twigPluginAsset']);
                $twig->addFunction($function);

                return $twig;
            })
        );
    }
}
