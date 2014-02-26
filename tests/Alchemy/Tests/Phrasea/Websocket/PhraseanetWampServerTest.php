<?php

namespace Alchemy\Tests\Phrasea\Websocket;

use Alchemy\Phrasea\Websocket\PhraseanetWampServer;

class PhraseanetWampServerTest extends \PhraseanetTestCase
{
    public function testOpenConnectionConnected()
    {
        $topicsManager = $this->createTopicsManagerMock();
        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $topicsManager->expects($this->once())
            ->method('openConnection')
            ->with($conn);

        $server = new PhraseanetWampServer($topicsManager, $this->createLoggerMock());
        $server->onOpen($conn);
    }

    private function createTopicsManagerMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Websocket\Topics\TopicsManager')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
