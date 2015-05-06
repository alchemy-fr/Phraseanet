<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Plugin;

use Alchemy\Phrasea\Core\PhraseaEvents;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PluginServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!isset($app['plugins.enabled'])) {
            $app['plugins.enabled'] = true;
        }

        if (!isset($app['plugins.path'])) {
            $app['plugins.path'] = __DIR__ . '/../../../../plugins';
        }

        $filename = $app['plugins.path'] . '/plugins.php';
        /** @var Plugin[] $plugins */
        $plugins = file_exists($filename) ? require $filename : [];

        if ($app['plugins.enabled']) {
            foreach ($plugins as $plugin) {
                if ($plugin instanceof ServiceProviderInterface) {
                    $app->register($plugin);
                }
            }
        }

        $app['plugins.routes'] = $app->protect(function ($environment) use ($app, $plugins) {
            if (!$app['plugins.enabled']) {
                return;
            }

            if (!in_array($environment, ['web', 'api'])) {
                throw new \UnexpectedValueException(sprintf('Expects $environment to be web or api, got "%s"', $environment));
            }

            $pluginMethod = 'bind' . ucfirst($environment) . 'Routes';
            foreach ($plugins as $plugin) {
                $plugin->{$pluginMethod}($app);
            }
        });

        $app['plugins.commands'] = $app->share(function (Application $app) use ($plugins) {
            if (! $app['plugins.enabled']) {
                return [];
            }

            $commands = [];
            foreach ($plugins as $plugin) {
                $commands = array_merge($commands, $plugin->getCommands());
            }

            return $commands;
        });

        $app['plugins.repository'] = $app->share(function () use ($app) {
           return new PluginRepository($app['plugins.path']);
        });

        $app['plugins.manager'] = $app->share(function () use ($app) {
            return new PluginManager($app['plugins.repository'], $app['conf'], $app['plugins.path']);
        });

        $app['twig'] = $app->share($app->extend('twig', function (\Twig_Environment $twig) {
            $twig->addFunction(new \Twig_SimpleFunction('plugin_asset', function ($name, $asset) {
                return sprintf('/plugins/%s/%s', $name, ltrim($asset, '/'));
            }));
            $twig->addFunction(new \Twig_SimpleFunction('plugin_var_export', 'var_export'));
            return $twig;
        }));
    }

    public function boot(Application $app)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addListener(PhraseaEvents::INSTALL_FINISH, function () use ($app) {
            /** @var PluginManager $manager */
            $manager = $app['plugins.manager'];
            $manager->dump();
        });
    }
}
