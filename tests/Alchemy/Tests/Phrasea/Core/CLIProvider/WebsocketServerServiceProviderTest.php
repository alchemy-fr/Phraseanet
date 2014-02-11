<?php

namespace Alchemy\Tests\Phrasea\Core\CLIProvider;

class WebsocketServerServiceProviderTest extends ServiceProviderTestCase
{
    public function provideServiceDescription()
    {
        return [
            [
                'Alchemy\Phrasea\Core\CLIProvider\WebsocketServerServiceProvider',
                'ws.task-manager.broadcaster',
                'Alchemy\Phrasea\Websocket\Subscriber\TaskManagerBroadcasterSubscriber',
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\WebsocketServerServiceProvider',
                'ws.event-loop',
                'React\EventLoop\LoopInterface',
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\WebsocketServerServiceProvider',
                'ws.server.subscriber',
                'React\ZMQ\SocketWrapper',
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\WebsocketServerServiceProvider',
                'ws.server.application',
                'Ratchet\WebSocket\WsServerInterface',
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\WebsocketServerServiceProvider',
                'ws.server',
                'Ratchet\App',
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\WebsocketServerServiceProvider',
                'ws.server.phraseanet-server',
                'Alchemy\Phrasea\Websocket\PhraseanetWampServer',
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\WebsocketServerServiceProvider',
                'ws.server.logger',
                'Psr\Log\LoggerInterface',
            ],
        ];
    }
}
