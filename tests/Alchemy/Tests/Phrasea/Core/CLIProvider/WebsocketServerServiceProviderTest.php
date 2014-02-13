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
                'Alchemy\Phrasea\Websocket\Topics\Plugin\TaskManagerSubscriberPlugin',
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
            [
                'Alchemy\Phrasea\Core\CLIProvider\WebsocketServerServiceProvider',
                'ws.server.topics-manager.directives',
                'Alchemy\Phrasea\Websocket\Topics\DirectivesManager',
            ],
            [
                'Alchemy\Phrasea\Core\CLIProvider\WebsocketServerServiceProvider',
                'ws.server.consumer-manager',
                'Alchemy\Phrasea\Websocket\Consumer\ConsumerManager',
            ],
        ];
    }
}
