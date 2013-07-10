<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaExceptionHandlerSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\BridgeExceptionSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\FirewallSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\JsonRequestSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\DebuggerSubscriber;
use Silex\Provider\WebProfilerServiceProvider;
use Symfony\Component\HttpFoundation\Request;

return call_user_func(function($environment = PhraseaApplication::ENV_PROD) {

    $app = new PhraseaApplication($environment);

    $app['exception_handler'] = $app->share(function ($app) {
        return new PhraseaExceptionHandlerSubscriber($app['phraseanet.exception_handler']);
    });

    $app->before(function (Request $request) use ($app) {
        if (0 === strpos($request->getPathInfo(), '/setup')) {
            if (!$app['phraseanet.configuration-tester']->isBlank()) {
                return $app->redirectPath('homepage');
            }
        } else {
            $app['firewall']->requireSetup();
        }
    });

    $app->bindRoutes();

    if (PhraseaApplication::ENV_DEV === $app->getEnvironment()) {
        $app->register($p = new WebProfilerServiceProvider(), array(
            'profiler.cache_dir'    => $app['root.path'] . '/tmp/cache/profiler',
        ));
        $app->mount('/_profiler', $p);
    }

    $app['dispatcher'] = $app->share(
        $app->extend('dispatcher', function($dispatcher, PhraseaApplication $app){
            $dispatcher->addSubscriber(new BridgeExceptionSubscriber($app));
            $dispatcher->addSubscriber(new FirewallSubscriber());
            $dispatcher->addSubscriber(new JsonRequestSubscriber());
            $dispatcher->addSubscriber(new DebuggerSubscriber($app));

            return $dispatcher;
        })
    );

    return $app;
}, isset($environment) ? $environment : null);
