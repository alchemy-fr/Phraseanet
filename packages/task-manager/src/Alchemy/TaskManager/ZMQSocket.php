<?php

/*
 * This file is part of Alchemy Task Manager
 *
 * (c) 2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\TaskManager;

use Alchemy\TaskManager\Exception\RuntimeException;

class ZMQSocket
{
    private $context;
    private $socket;
    private $type;
    private $protocol;
    private $host;
    private $port;
    private $bound = false;

    public function __construct(\ZMQContext $context, $type, $protocol, $host, $port)
    {
        $this->context = $context;
        $this->type = $type;
        $this->socket = $context->getSocket($type);
        $this->protocol = $protocol;
        $this->host = $host;
        $this->port = $port;
    }

    public function bind()
    {
        // libzmq releases unbound endpoints asynchronously, an endpoint
        // that was just unbound may still be in use for a short time.
        $attempts = 10;
        while (true) {
            try {
                $this->socket->bind("$this->protocol://$this->host:$this->port");
                break;
            } catch (\ZMQSocketException $e) {
                if (0 === --$attempts) {
                    throw new RuntimeException("Unable to bind socket to $this->protocol://$this->host:$this->port. Is another one already bound ?", $e->getCode(), $e);
                }
                usleep(20000);
            }
        }
        $this->bound = true;
    }

    public function unbind()
    {
        try {
            // this method does not exist on older versions
            if (method_exists($this->socket, 'unbind')) {
                $this->socket->unbind("$this->protocol://$this->host:$this->port");
            }
        } catch (\ZMQSocketException $e) {
            throw new RuntimeException("Unable to unbind socket on $this->protocol://$this->host:$this->port.", $e->getCode(), $e);
        }
        unset($this->socket);
        $this->socket = $this->context->getSocket($this->type);
        $this->bound = false;
    }

    public function isBound()
    {
        return $this->bound;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->socket, $name)) {
            return call_user_func_array(array($this->socket, $name), $arguments);
        }

        throw new \BadMethodCallException(sprintf('Method %s is undefined', $name));
    }

    public static function create(\ZMQContext $context, $type, $protocol, $host, $port)
    {
        return new static($context, $type, $protocol, $host, $port);
    }
}
