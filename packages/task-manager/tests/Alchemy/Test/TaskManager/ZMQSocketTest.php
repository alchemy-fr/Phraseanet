<?php

namespace Alchemy\Test\TaskManager;

use Alchemy\TaskManager\ZMQSocket;

class ZMQSocketTest extends TestCase
{
    public function testBindUnbindMultipleTimes()
    {
        $context = new \ZMQContext;
        $socket = new ZMQSocket($context, \ZMQ::SOCKET_REP, 'tcp', '127.0.0.1', 6660);

        $socket->bind();
        $this->assertTrue($socket->isBound());
        $socket->unbind();
        $this->assertFalse($socket->isBound());
        $socket->bind();
        $this->assertTrue($socket->isBound());
        $socket->unbind();
        $this->assertFalse($socket->isBound());
    }

    public function testBindUnbindInParallelOnSamePort()
    {
        $context1 = new \ZMQContext;
        $socket1 = new ZMQSocket($context1, \ZMQ::SOCKET_REP, 'tcp', '127.0.0.1', 6660);

        $context2 = new \ZMQContext;
        $socket2 = new ZMQSocket($context2, \ZMQ::SOCKET_REP, 'tcp', '127.0.0.1', 6660);

        $socket1->bind();
        $socket1->unbind();
        $socket2->bind();
        $this->assertTrue($socket2->isBound());
        $socket2->unbind();
        $this->assertFalse($socket2->isBound());
    }
}
