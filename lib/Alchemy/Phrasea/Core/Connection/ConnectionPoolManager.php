<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Connection;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\DriverManager;
use Psr\Log\LoggerInterface;

class ConnectionPoolManager
{
    /**
     * @var \PDO[]
     */
    private $connections = [];
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    public function __destruct()
    {
        $this->closeAll();
        $this->connections = [];
    }

    public function closeAll()
    {
        foreach ($this->connections as $key => $conn) {
            $conn->close();
        }
    }

    public function opened()
    {
        return $this->filter(function($connection) {
            return $connection->isConnected();
        });
    }

    public function closed()
    {
        return $this->filter(function($connection) {
            return !$connection->isConnected();
        });
    }

    public function filter(Callable $callback)
    {
        return array_filter($this->connections, $callback);
    }

    public function add(Connection $connection)
    {
        $key = md5(serialize($connection->getParams()));
        if (!isset($this->connections[$key])) {
            $this->connections[$key] = $connection;
        }
    }

    public function get(array $params)
    {
        $params = array_replace([
            'driver'  => 'pdo_mysql',
            'charset' => 'UTF8',
        ], $params);

        $key = md5(serialize($params));

        if (isset($this->connections[$key])) {
            return $this->connections[$key];
        }

        return $this->connections[$key] = DriverManager::getConnection($params);
    }
}
