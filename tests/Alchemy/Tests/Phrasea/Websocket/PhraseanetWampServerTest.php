<?php

namespace Alchemy\Tests\Phrasea\Websocket;

use Alchemy\Phrasea\Websocket\PhraseanetWampServer;

class PhraseanetWampServerTest extends \PhraseanetTestCase
{
    public function testOpenConnectionNotConnected()
    {
        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $conn->Session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $conn->Session->expects($this->once())
            ->method('has')
            ->with('usr_id')
            ->will($this->returnValue(false));
        $conn->expects($this->once())
             ->method('close');

        $server = new PhraseanetWampServer($this->createSocketWrapperMock(), $this->createLoggerMock());
        $server->onOpen($conn);
    }

    public function testOpenConnectionConnected()
    {
        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $conn->Session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $conn->Session->expects($this->once())
            ->method('has')
            ->with('usr_id')
            ->will($this->returnValue(true));
        $conn->expects($this->never())
             ->method('close');

        $server = new PhraseanetWampServer($this->createSocketWrapperMock(), $this->createLoggerMock());
        $server->onOpen($conn);
    }

    private function createSocketWrapperMock()
    {
        return $this->getMockBuilder('React\ZMQ\SocketWrapper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createLoggerMock()
    {
        return $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
