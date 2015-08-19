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

use ArrayObject;
use Pimple;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Twig_Environment;
use Twig_SimpleFunction;

class PluginServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['plugin.actionbar'] = $app->share(function () {
            return new Pimple();
        });
        $app['plugin.workzone'] = $app->share(function () {
            return new Pimple();
        });

        $app['plugin.actionbar'] = $app->share(function () {
            return new \Pimple();
        });

        $app['plugin.locale.textdomains'] = new \ArrayObject();

        // Routes will be bound after all others
        // Add a new controller provider can be added as follows
        // $app['plugin.controller_providers'][] = array('/prefix', 'controller_provider_service_key');
        $app['plugin.controller_providers.root'] = new ArrayObject();

        // Routes will be bound after all others
        // Add a new controller provider can be added as follows
        $app['plugin.controller_providers.api'] = new \ArrayObject();
    }

    public function boot(Application $app)
    {
        foreach ($app['plugin.locale.textdomains'] as $textdomain => $dir) {
            bind_textdomain_codeset($textdomain, 'UTF-8');
            bindtextdomain($textdomain, $dir);
        }

        $app['twig'] = $app->share(
            $app->extend('twig', function (Twig_Environment $twig) {
                $function = new Twig_SimpleFunction('plugin_asset', array('Alchemy\Phrasea\Plugin\Management\AssetsManager', 'twigPluginAsset'));
                $twig->addFunction($function);

                return $twig;
            })
        );
    }
}
