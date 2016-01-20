<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaExceptionHandlerSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\BridgeExceptionSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\FirewallSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\JsonRequestSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\DebuggerSubscriber;
use Alchemy\Phrasea\Core\Provider\WebProfilerServiceProvider as PhraseaWebProfilerServiceProvider;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Silex\Provider\WebProfilerServiceProvider;
use Sorien\Provider\DoctrineProfilerServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

return call_user_func(function ($environment = PhraseaApplication::ENV_PROD) {
    $app = new PhraseaApplication($environment);
    $app->loadPlugins();

    $app['exception_handler'] = $app->share(function ($app) {
        return new PhraseaExceptionHandlerSubscriber($app['phraseanet.exception_handler']);
    });
    $app['monolog'] = $app->share($app->extend('monolog', function (Logger $monolog) {
        $monolog->pushProcessor(new WebProcessor());

        return $monolog;
    }));

    $app->before(function (Request $request) use ($app) {

        if (0 === strpos($request->getPathInfo(), '/setup')) {
            if (!$app['phraseanet.configuration-tester']->isInstalled()) {
                if (!$app['phraseanet.configuration-tester']->isBlank()) {
                    if ('setup_upgrade_instructions' !== $app['request']->attributes->get('_route')) {
                        return $app->redirectPath('setup_upgrade_instructions');
                    }
                }
            } elseif (!$app['phraseanet.configuration-tester']->isBlank()) {
                return $app->redirectPath('homepage');
            }
        } else {
            if (false === strpos($request->getPathInfo(), '/include/minify')) {
                $app['firewall']->requireSetup();
            }
        }
    }, Application::EARLY_EVENT);

    $app->bindRoutes();

    if (PhraseaApplication::ENV_DEV === $app->getEnvironment()) {
        $app->register($p = new WebProfilerServiceProvider(), [
            'profiler.cache_dir' => $app['cache.path'].'/profiler',
        ]);

        $app->register(new PhraseaWebProfilerServiceProvider());

        $app->mount('/_profiler', $p);

        if ($app['phraseanet.configuration-tester']->isInstalled()) {
            $app->register(new DoctrineProfilerServiceProvider());
            $app['db'] = $app->share(function (PhraseaApplication $app) {
                return $app['orm.em']->getConnection();
            });
        }
    }

    $app['dispatcher'] = $app->share(
        $app->extend('dispatcher', function (EventDispatcherInterface $dispatcher, PhraseaApplication $app) {
            $dispatcher->addSubscriber(new BridgeExceptionSubscriber($app));
            $dispatcher->addSubscriber(new FirewallSubscriber());
            $dispatcher->addSubscriber(new JsonRequestSubscriber());
            $dispatcher->addSubscriber(new DebuggerSubscriber($app));

            return $dispatcher;
        })
    );

    return $app;
}, isset($environment) ? $environment : PhraseaApplication::ENV_PROD);
