<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Connection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

class ConnectionPoolManager
{
    /**
     * @var Connection[]
     */
    private $connections = [];

    public function __destruct()
    {
        $this->closeAll();
    }

    public function closeAll()
    {
        foreach ($this->connections as $conn) {
            $conn->close();
        }
    }

    public function opened()
    {
        return $this->filter(function(Connection $connection) {
            return $connection->isConnected();
        });
    }

    public function closed()
    {
        return $this->filter(function(Connection $connection) {
            return !$connection->isConnected();
        });
    }

    /**
     * @param callable $callback
     * @return Connection[]
     */
    public function filter(Callable $callback)
    {
        return array_filter($this->connections, $callback);
    }

    /**
     * Add a connection to the pool
     *
     * @param Connection $connection
     */
    public function add(Connection $connection)
    {
        $key = md5(serialize($connection->getParams()));
        if (isset($this->connections[$key]) && $connection !== $this->connections[$key]) {
            throw new \InvalidArgumentException('Expects a non registered connection.');
        }
        $this->connections[$key] = $connection;
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

    public function all()
    {
        return $this->connections;
    }
}
