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

class ReconnectableConnection implements ConnectionInterface
{
    const MYSQL_CONNECTION_TIMED_WAIT_CODE = 2006;

    /** @var Connection */
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    function prepare($prepareString)
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    function query()
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    function quote($input, $type=\PDO::PARAM_STR)
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    function exec($statement)
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    function lastInsertId($name = null)
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    function beginTransaction()
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    function commit()
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    function rollBack()
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    function errorCode()
    {
        return $this->tryMethod(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    function errorInfo()
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
            set_error_handler(function ($errno, $errstr) { throw new \Exception($errstr, $errno); });
            $ret = call_user_func_array([$this->connection, $method], $args);
            restore_error_handler();

            return $ret;
        } catch (\Exception $exception) {
            restore_error_handler();
            $e = $exception;
            while ($e->getPrevious() && !$e instanceof \PDOException) {
                $e = $e->getPrevious();
            }
            if ($e instanceof \PDOException && $e->errorInfo[1] == self::MYSQL_CONNECTION_TIMED_WAIT_CODE) {
                $this->connection->close();
                $this->connection->connect();

                return call_user_func_array([$this->connection, $method], $args);
            }
            if ((false !== strpos($exception->getMessage(), 'MySQL server has gone away')) || (false !== strpos($exception->getMessage(), 'errno=32 Broken pipe'))) {
                $this->connection->close();
                $this->connection->connect();

                return call_user_func_array([$this->connection, $method], $args);
            }

            throw $e;
        }
    }
}
