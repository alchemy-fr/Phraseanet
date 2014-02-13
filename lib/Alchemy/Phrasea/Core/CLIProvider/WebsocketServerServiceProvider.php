<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\CLIProvider;

use Alchemy\Phrasea\Websocket\Consumer\ConsumerManager;
use Alchemy\Phrasea\Websocket\Topics\Directive;
use Alchemy\Phrasea\Websocket\Topics\DirectivesManager;
use Alchemy\Phrasea\Websocket\Subscriber\TaskManagerBroadcasterSubscriber;
use Alchemy\Phrasea\Websocket\PhraseanetWampServer;
use Alchemy\Phrasea\Websocket\Topics\Plugin\TaskManagerSubscriberPlugin;
use Alchemy\Phrasea\Websocket\Topics\TopicsManager;
use Ratchet\App;
use Ratchet\Session\SessionProvider;
use Ratchet\Wamp\WampServer;
use Silex\Application;
use Silex\ServiceProviderInterface;
use React\EventLoop\Factory as EventLoopFactory;

class WebsocketServerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['ws.publisher.options'] = $app->share(function (Application $app) {
            return array_replace([
                'protocol' => 'tcp',
                'host' => '127.0.0.1',
                'port' => 13598,
            ], $app['conf']->get(['main', 'task-manager', 'publisher'], []));
        });

        $app['ws.task-manager.broadcaster'] = $app->share(function (Application $app) {
            return TaskManagerBroadcasterSubscriber::create($app['ws.publisher.options']);
        });

        $app['ws.event-loop'] = $app->share(function () {
            return EventLoopFactory::create();
        });

        $app['ws.server.subscriber'] = $app->share(function (Application $app) {
            return new TaskManagerSubscriberPlugin($app['ws.publisher.options'], $app['ws.event-loop'], $app['ws.server.logger']);
        });

        $app['ws.server.application'] = $app->share(function (Application $app) {
            return new SessionProvider(
                new WampServer($app['ws.server.phraseanet-server']), $app['session.storage.handler']
            );
        });

        $app['ws.server.phraseanet-server'] = $app->share(function (Application $app) {
            return new PhraseanetWampServer($app['ws.server.topics-manager'], $app['ws.server.logger']);
        });

        $app['ws.server.logger'] = $app->share(function (Application $app) {
            return $app['task-manager.logger'];
        });

        $app['ws.server.topics-manager.directives.conf'] = $app->share(function (Application $app) {
            return [
                new Directive(TopicsManager::TOPIC_TASK_MANAGER, true, ['task-manager']),
            ];
        });

        $app['ws.server.topics-manager.directives'] = $app->share(function (Application $app) {
            return new DirectivesManager($app['ws.server.topics-manager.directives.conf']);
        });

        $app['ws.server.consumer-manager'] = $app->share(function (Application $app) {
            return new ConsumerManager();
        });

        $app['ws.server.topics-manager'] = $app->share(function (Application $app) {
            $manager = new TopicsManager($app['ws.server.topics-manager.directives'], $app['ws.server.consumer-manager']);
            $manager->attach($app['ws.server.subscriber']);

            return $manager;
        });

        $app['ws.server.options'] = $app->share(function (Application $app) {
            return array_replace([
                'host' => 'localhost',
                'port' => 9090,
                'ip'   => '127.0.0.1',
            ], $app['conf']->get(['main', 'websocket-server'], []));
        });

        $app['ws.server'] = $app->share(function (Application $app) {
            $options = $app['ws.server.options'];

            $server = new App($options['host'], $options['port'], $options['ip'], $app['ws.event-loop']);
            $server->route('/websockets', $app['ws.server.application']);

            return $server;
        });
    }

    public function boot(Application $app)
    {
    }
}
