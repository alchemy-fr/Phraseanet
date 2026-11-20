<?php

namespace Alchemy\Queue\Tests;

trait RequiresAmqpServer
{
    /**
     * Connection parameters of the AMQP server used by integration tests,
     * configurable with the AMQP_HOST, AMQP_PORT, AMQP_USER and AMQP_PASSWORD
     * environment variables.
     */
    private static function getAmqpServerParameters()
    {
        return [
            'host' => getenv('AMQP_HOST') ?: '127.0.0.1',
            'port' => (int) (getenv('AMQP_PORT') ?: 5672),
            'user' => getenv('AMQP_USER') ?: 'guest',
            'password' => getenv('AMQP_PASSWORD') ?: 'guest',
        ];
    }

    private static function skipIfAmqpServerIsUnavailable()
    {
        if (!extension_loaded('amqp')) {
            self::markTestSkipped('The amqp extension is required.');
        }

        $parameters = self::getAmqpServerParameters();
        $socket = @fsockopen($parameters['host'], $parameters['port'], $errno, $errstr, 1);

        if (false === $socket) {
            self::markTestSkipped(sprintf('No AMQP server available on %s:%d.', $parameters['host'], $parameters['port']));
        }

        fclose($socket);
    }
}
