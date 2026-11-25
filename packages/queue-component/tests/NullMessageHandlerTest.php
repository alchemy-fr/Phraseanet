<?php
namespace Alchemy\Queue\Tests;

use Alchemy\Queue\Message;
use Alchemy\Queue\NullMessageHandler;

class NullMessageHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testAlwaysAcceptsMessage()
    {
        $message = new Message('');
        $handler = new NullMessageHandler();

        $this->assertTrue($handler->accepts($message), 'Handler should accept message');
    }

    public function testHandleIsVoidOfSideEffects()
    {
        $message = new ForbiddenMessage($this);
        $handler = new NullMessageHandler();

        // Test will error if handle calls any message methods
        $this->assertNull($handler->handle($message));
    }
}

class ForbiddenMessage extends Message
{
    private $test;

    public function __construct(\Alchemy\Queue\Tests\NullMessageHandlerTest $test)
    {
        $this->test = $test;
    }

    public function getBody()
    {
        $this->test->fail('getBody should not be called.');
    }

    public function getCorrelationId()
    {
        $this->test->fail('getCorrelationId should not be called.');
    }
}
