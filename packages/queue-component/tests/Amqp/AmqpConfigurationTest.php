<?php

namespace Alchemy\Queue\Tests\Amqp;

use Alchemy\Queue\Amqp\AmqpConfiguration;

class AmqpConfigurationTest extends \PHPUnit\Framework\TestCase
{

    public function testHasDefaultValues()
    {
        $configuration = new AmqpConfiguration();

        $this->assertEquals('localhost', $configuration->getHost());
        $this->assertEquals('/', $configuration->getVhost());
        $this->assertEquals(5672, $configuration->getPort());
        $this->assertEquals('guest', $configuration->getUser());
        $this->assertEquals('guest', $configuration->getPassword());
        $this->assertEquals('alchemy-exchange', $configuration->getExchange());
        $this->assertEquals('', $configuration->getDeadLetterExchange());
        $this->assertEquals('alchemy-queue', $configuration->getQueue());

        $configuration = AmqpConfiguration::parse([]);

        $this->assertEquals('localhost', $configuration->getHost());
        $this->assertEquals('/', $configuration->getVhost());
        $this->assertEquals(5672, $configuration->getPort());
        $this->assertEquals('guest', $configuration->getUser());
        $this->assertEquals('guest', $configuration->getPassword());
        $this->assertEquals('alchemy-exchange', $configuration->getExchange());
        $this->assertEquals('', $configuration->getDeadLetterExchange());
        $this->assertEquals('alchemy-queue', $configuration->getQueue());
    }

    public function testParseCorrectlyAssignsValues()
    {
        $parameters = [
            'host' => '127.0.0.1',
            'vhost' => '/mock-vhost/',
            'port' => 5555,
            'user' => 'mock-user',
            'password' => 'mock-password',
            'exchange' => 'mock-exchange',
            'dead-letter-exchange' => 'mock-dead-exchange',
            'queue' => 'mock-queue'
        ];

        $configuration = AmqpConfiguration::parse($parameters);

        $this->assertEquals('127.0.0.1', $configuration->getHost());
        $this->assertEquals('/mock-vhost/', $configuration->getVhost());
        $this->assertEquals(5555, $configuration->getPort());
        $this->assertEquals('mock-user', $configuration->getUser());
        $this->assertEquals('mock-password', $configuration->getPassword());
        $this->assertEquals('mock-exchange', $configuration->getExchange());
        $this->assertEquals('mock-dead-exchange', $configuration->getDeadLetterExchange());
        $this->assertEquals('mock-queue', $configuration->getQueue());
    }

    public function testToConnectionArrayReturnsCorrectConnectionParameters()
    {
        $configuration = new AmqpConfiguration();

        $expected = [
            'host' => 'localhost',
            'port' => 5672,
            'vhost' => '/',
            'login' => 'guest',
            'password' => 'guest'
        ];

        $this->assertEquals($expected, $configuration->toConnectionArray());
    }
}
