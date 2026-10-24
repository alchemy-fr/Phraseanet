<?php

namespace Alchemy\Tests\Phrasea\TaskManager\Event;

use Alchemy\Phrasea\TaskManager\Event\PhraseanetIndexerStopperSubscriber;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PhraseanetIndexerStopperSubscriberTest extends \PhraseanetTestCase
{
    public function testSocketmessageIsSentOnStop()
    {
        $port = 12778;
        if (false === $socket = stream_socket_server("tcp://127.0.0.1:$port", $errno, $errstr)) {
            $this->markTestSkipped('Unable to create socket');
        }

        $event = $this->createStub('Alchemy\TaskManager\Event\JobEvent');
        $subscriber = new PhraseanetIndexerStopperSubscriber($port);
        $subscriber->onStopRequest($event);

        $conn = stream_socket_accept($socket);
        $message = fread($conn, 1024);
        fclose($conn);
        fclose($socket);
        $this->assertEquals("Q\r\n", $message);
    }
}
