<?php
/*
 * This file is part of Phraseanet Mail-Log plugin
 *
 * (c) 2005-2020 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\DevToolsPlugin\EventsLogger;


use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Plugin\PluginProviderInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Service implements PluginProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['devToolsPlugin.eventsLoggerSubscriber'] = $app->share(
            function() use($app) { return  new Subscriber($app['conf']); }
        );

        $app['dispatcher'] = $app->share(
            $app->extend('dispatcher', function (EventDispatcherInterface $dispatcher) use ($app) {
                $dispatcher->addSubscriber($app['devToolsPlugin.eventsLoggerSubscriber']);

                return $dispatcher;
            })
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        // no-op
    }

    /**
     * {@inheritdoc}
     */
    public static function create(PhraseaApplication $app)
    {
        return new static();
    }
}

