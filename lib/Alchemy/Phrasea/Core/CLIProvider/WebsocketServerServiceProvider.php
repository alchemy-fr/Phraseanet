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

use Alchemy\Phrasea\Websocket\Subscriber\TaskManagerBroadcasterSubscriber;
use Alchemy\Phrasea\Websocket\PhraseanetWampServer;
use Ratchet\App;
use Ratchet\Session\SessionProvider;
use Ratchet\Wamp\WampServer;
use React\ZMQ\Context;
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
            $options = $app['ws.publisher.options'];
            $context = new Context($app['ws.event-loop']);

            $pull = $context->getSocket(\ZMQ::SOCKET_SUB);
            $pull->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, "");
            $pull->connect(sprintf('%s://%s:%s', $options['protocol'], $options['host'], $options['port']));

            $logger = $app['ws.server.logger'];
            $pull->on('error', function ($e) use ($logger) {
                $logger->error('TaskManager Subscriber received an error.', ['exception' => $e]);
            });

            return $pull;
        });

        $app['ws.server.application'] = $app->share(function (Application $app) {
            return new SessionProvider(
                new WampServer($app['ws.server.phraseanet-server']), $app['session.storage.handler']
            );
        });

        $app['ws.server.phraseanet-server'] = $app->share(function (Application $app) {
            return new PhraseanetWampServer($app['ws.server.subscriber'], $app['ws.server.logger']);
        });

        $app['ws.server.logger'] = $app->share(function (Application $app) {
            return $app['task-manager.logger'];
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
