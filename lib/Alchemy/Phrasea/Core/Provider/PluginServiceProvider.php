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

use Alchemy\Phrasea\Authorization\AuthorizationChecker;
use Alchemy\Phrasea\Plugin\PluginManager;
use Alchemy\Phrasea\Plugin\Schema\ManifestValidator;
use Alchemy\Phrasea\Plugin\Schema\PluginValidator;
use ArrayObject;
use JsonSchema\Validator as JsonValidator;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Twig\Environment;
use Twig\TwigFunction;

class PluginServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['plugins.schema'] = realpath(__DIR__ . '/../../../../conf.d/plugin-schema.json');

        $app['plugins.json-validator'] = function (Application $app) {
            return new JsonValidator();
        };

        $app['plugins.manifest-validator'] = function (Application $app) {
            return ManifestValidator::create($app);
        };

        $app['plugins.plugins-validator'] = function (Application $app) {
            return new PluginValidator($app['plugins.manifest-validator']);
        };

        $app['plugins.manager'] = function (Application $app) {
            return new PluginManager($app['plugin.path'], $app['plugins.plugins-validator'], $app['conf']);
        };
        // All plugins, indexed by their name
        $app['plugins'] = function () {
            return new Container();
        };

        $app['plugin.workzone.basket.actionbar'] = function () {
            return new Container();
        };
        $app['plugin.actionbar'] = function () {
            return new Container();
        };
        $app['plugin.workzone'] = function () {
            return new Container();
        };
        $app['plugin.filter_by_authorization'] = $app->protect(function ($pluginZone, $attributes = 'VIEW') use ($app) {
            /** @var \Pimple\Container $container */
            $container = $app['plugin.' . $pluginZone];
            /** @var AuthorizationChecker $authorizationChecker */
            $authorizationChecker = $app['phraseanet.authorization_checker'];

            $plugins = [];
            foreach ($container->keys() as $pluginKey) {
                $plugin = $container[$pluginKey];

                if ($authorizationChecker->isGranted($attributes, $plugin)) {
                    $plugins[$pluginKey] = $plugin;
                }
            }

            return $plugins;
        });

        $app['plugin.locale.textdomains'] = new ArrayObject();

        // Routes will be bound after all others
        // Add a new controller provider can be added as follows
        // $app['plugin.controller_providers'][] = array('/prefix', 'controller_provider_service_key');
        $app['plugin.controller_providers.root'] = new ArrayObject();

        // Routes will be bound after all others
        // Add a new controller provider can be added as follows
        // $app['plugin.controller_providers'][] = array('/prefix', 'controller_provider_service_key');
        $app['plugin.controller_providers.api'] = new ArrayObject();

        $app->extend('twig', function (Environment $twig) {
            $function = new TwigFunction('plugin_asset', array('Alchemy\Phrasea\Plugin\Management\AssetsManager', 'twigPluginAsset'));
            $twig->addFunction($function);

            return $twig;
        });
    }

    public function boot(Application $app)
    {
        foreach ($app['plugin.locale.textdomains'] as $textdomain => $dir) {
            bind_textdomain_codeset($textdomain, 'UTF-8');
            bindtextdomain($textdomain, $dir);
        }
    }
}
