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

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\DriverManager;

class ConnectionProvider
{
    private $config;
    /**
     * @var Connection[]
     */
    private $connections = [];
    private $eventManager;

    public function __construct(Configuration $config, EventManager $eventManager)
    {
        $this->config = $config;
        $this->eventManager = $eventManager;
    }

    public function __destruct()
    {
        foreach ($this->connections as $conn) {
            $conn->close();
        }

        $this->connections = [];
    }

    /**
     * @param $params
     *
     * @return Connection
     */
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

        return $this->connections[$key] = new ReconnectableConnection(DriverManager::getConnection($params, $this->config, $this->eventManager));
    }
}
