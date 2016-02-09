<?php

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\BridgeExceptionSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\DebuggerSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\FirewallSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\JsonRequestSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaExceptionHandlerSubscriber;
use Alchemy\Phrasea\Core\Middleware\SetupMiddlewareProvider;
use Monolog\Logger;
use Symfony\Bridge\Monolog\Processor\WebProcessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ApplicationLoader
{

    public function buildWebApplication($environment = Application::ENV_PROD, $forceDebug = false)
    {
        $env = new Environment($environment, false);
        $app = new Application($env);

        $app->register(new SetupMiddlewareProvider());
        $app->loadPlugins();

        $app['exception_handler'] = $app->share(function ($app) {
            return new PhraseaExceptionHandlerSubscriber($app['phraseanet.exception_handler']);
        });

        $app['monolog'] = $app->share($app->extend('monolog', function (Logger $monolog) {
            $monolog->pushProcessor(new WebProcessor());

            return $monolog;
        }));

        $app->before($app['setup.validate-config'], Application::EARLY_EVENT);
        $app->bindRoutes();

        $app['dispatcher'] = $app->share(
            $app->extend('dispatcher', function (EventDispatcherInterface $dispatcher, Application $app) {
                $dispatcher->addSubscriber(new BridgeExceptionSubscriber($app));
                $dispatcher->addSubscriber(new FirewallSubscriber());
                $dispatcher->addSubscriber(new JsonRequestSubscriber());

                if ($app->isDebug()){
                    $dispatcher->addSubscriber(new DebuggerSubscriber($app));
                }

                return $dispatcher;
            })
        );

        return $app;
    }
}
