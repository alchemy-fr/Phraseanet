<?php

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\DebuggerSubscriber;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class BaseApplicationLoader
{
    /**
     * @param string $environment
     * @param bool $forceDebug
     * @return Application
     */
    public function buildApplication($environment = Application::ENV_PROD, $forceDebug = false)
    {
        $app = $this->createApplication($environment, $forceDebug);

        $this->prePluginRegister($app);
        $app->loadPlugins();

        $app['exception_handler'] = $app->share([$this, 'exceptionHandlerRegister']);

        $app['monolog'] = $app->share($app->extend('monolog', function (Logger $monolog) {
            $monolog->pushProcessor(new WebProcessor());

            return $monolog;
        }));

        $this->bindRoutes($app);

        $subscribers = $this->getDispatcherSubscribersFor($app);

        if ($subscribers) {
            $app['dispatcher'] = $app->share(
                $app->extend(
                    'dispatcher',
                    function (EventDispatcherInterface $dispatcher) use ($subscribers) {
                        foreach ($subscribers as $subscriber) {
                            $dispatcher->addSubscriber($subscriber);
                        }

                        return $dispatcher;
                    }
                )
            );
        }

        return $app;
    }

    abstract protected function prePluginRegister(Application $app);

    abstract protected function exceptionHandlerRegister(Application $app);

    abstract protected function bindRoutes(Application $app);

    /**
     * @param Application $app
     * @return EventSubscriberInterface[]
     */
    protected function getDispatcherSubscribersFor(Application $app)
    {
        return $app->isDebug() ? [new DebuggerSubscriber($app)] : [];
    }

    /**
     * @param string $environment
     * @param bool $forceDebug
     * @return Application
     */
    private function createApplication($environment = Application::ENV_PROD, $forceDebug = false)
    {
        return new Application(new Environment($environment, $forceDebug));
    }
}
