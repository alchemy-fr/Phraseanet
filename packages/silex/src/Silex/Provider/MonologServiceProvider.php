<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler;
use Monolog\ErrorHandler;
use Silex\Application;
use Silex\Api\BootableProviderInterface;
use Silex\Api\EventListenerProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Silex\EventListener\LogListener;

/**
 * Monolog Provider.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class MonologServiceProvider implements ServiceProviderInterface, BootableProviderInterface, EventListenerProviderInterface
{
    public function register(Container $app)
    {
        $app['logger'] = function () use ($app) {
            return $app['monolog'];
        };

        $app['monolog.logger.class'] = Logger::class;

        $app['monolog'] = function ($app) {
            $log = new $app['monolog.logger.class']($app['monolog.name']);

            $log->pushHandler(new Handler\GroupHandler($app['monolog.handlers']));

            return $log;
        };

        $app['monolog.formatter'] = function () {
            return new LineFormatter();
        };

        $app['monolog.handler'] = $defaultHandler = function () use ($app) {
            $level = MonologServiceProvider::translateLevel($app['monolog.level']);

            $handler = new Handler\StreamHandler($app['monolog.logfile'], $level, $app['monolog.bubble'], $app['monolog.permission']);
            $handler->setFormatter($app['monolog.formatter']);

            return $handler;
        };

        $app['monolog.handlers'] = function () use ($app, $defaultHandler) {
            $handlers = [];

            // enables the default handler if a logfile was set or the monolog.handler service was redefined
            if ($app['monolog.logfile'] || $defaultHandler !== $app->raw('monolog.handler')) {
                $handlers[] = $app['monolog.handler'];
            }

            return $handlers;
        };

        $app['monolog.level'] = function () {
            return Level::Debug;
        };

        $app['monolog.listener'] = function () use ($app) {
            return new LogListener($app['logger'], $app['monolog.exception.logger_filter']);
        };

        $app['monolog.name'] = 'app';
        $app['monolog.bubble'] = true;
        $app['monolog.permission'] = null;
        $app['monolog.exception.logger_filter'] = null;
        $app['monolog.logfile'] = null;
        $app['monolog.use_error_handler'] = function ($app) {
            return !$app['debug'];
        };
    }

    public function boot(Application $app)
    {
        if ($app['monolog.use_error_handler']) {
            ErrorHandler::register($app['monolog']);
        }
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        if (isset($app['monolog.listener'])) {
            $dispatcher->addSubscriber($app['monolog.listener']);
        }
    }

    public static function translateLevel($name)
    {
        try {
            return Logger::toMonologLevel($name);
        } catch (\Psr\Log\InvalidArgumentException $e) {
            throw new \InvalidArgumentException("Provided logging level '$name' does not exist. Must be a valid monolog logging level.", 0, $e);
        }
    }
}
