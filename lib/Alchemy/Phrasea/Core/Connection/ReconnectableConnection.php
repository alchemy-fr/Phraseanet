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

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Psr\Log\LoggerInterface;

class ReconnectableConnection implements ConnectionInterface
{
    const MYSQL_CONNECTION_TIMED_WAIT_CODE = 2006;

    /** @var Connection */
    private $connection;
    private $logger;

    public function __construct(ConnectionInterface $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($prepareString)
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type=\PDO::PARAM_STR)
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function exec($statement)
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function __call($method, $args)
    {
        return $this->tryMethod($method, $args);
    }

    private function tryMethod($method, $args)
    {
        try {
            return call_user_func_array([$this->connection, $method], $args);
        } catch (\Exception $exception) {
            $e = $exception;
            while ($e->getPrevious() && !$e instanceof \PDOException) {
                $e = $e->getPrevious();
            }
            if ($e instanceof \PDOException && $e->errorInfo[1] == self::MYSQL_CONNECTION_TIMED_WAIT_CODE) {
                $this->connection->close();
                $this->connection->connect();
                $this->logger->notice('Connection to MySQL lost, reconnect okay.');

                return call_user_func_array([$this->connection, $method], $args);
            }
            if (
                (false !== strpos($exception->getMessage(), 'MySQL server has gone away'))
                || (false !== strpos($exception->getMessage(), 'Error while sending QUERY packet'))
                || (false !== strpos($exception->getMessage(), 'errno=32 Broken pipe'))
            ) {
                $this->connection->close();
                $this->connection->connect();
                $this->logger->notice('Connection to MySQL lost, reconnect okay.');

                return call_user_func_array([$this->connection, $method], $args);
            }
            $this->logger->critical('Connection to MySQL lost, unable to reconnect.', ['exception' => $exception]);

            throw $e;
        }
    }
}
