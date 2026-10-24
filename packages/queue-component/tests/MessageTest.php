<?php

namespace Alchemy\Queue\Tests;

use Alchemy\Queue\Message;

class MessageTest extends \PHPUnit\Framework\TestCase
{

    public function testMessageHasBody()
    {
        $message = new Message('mock-body');

        $this->assertEquals('mock-body', $message->getBody());
    }

    public function testMessageHasDefaultCorrelationId()
    {
        $message = new Message('mock-body');

        $this->assertNotEmpty($message->getCorrelationId(), 'Message should have default correlation ID');
    }

    public function testMessageCorrelationIdCanBeOverridden()
    {
        $message = new Message('mock-body', 'mock-correlation-id');

        $this->assertEquals('mock-correlation-id', $message->getCorrelationId());
    }

    public function testMessageCreatedFromArrayHasJsonEncodedBody()
    {
        $data = ['mock-key'=>'mock-value'];

        $message = Message::fromArray($data, 'mock-id');

        $this->assertEquals('mock-id', $message->getCorrelationId());
        $this->assertEquals($data, json_decode($message->getBody(), true));
    }
}
