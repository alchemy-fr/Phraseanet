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

use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Psr\Log\LoggerInterface;

class ReconnectableConnection implements ConnectionInterface
{
    const MAX_DELAY = 30;
    const MYSQL_CONNECTION_TIMED_WAIT_CODE = 2006;

    /** @var Connection */
    protected $connection;
    /** @var LoggerInterface */
    protected $logger;
    protected $isConnected;
    protected $tries;
    protected $triesDone;
    protected $infiniteMode;

    public function __construct(DoctrineConnection $connection, LoggerInterface $logger, $tries = 3)
    {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->tries = $tries;
        $this->triesDone = 0;
        $this->infiniteMode = $tries < 0;
    }

    public function setMaxTries($tries)
    {
        $this->infiniteMode = $tries < 0;
        $this->tries = $tries;
    }

    public function isConnected()
    {
        if (null === $this->isConnected) {
            $this->ping();
        }

        return $this->isConnected && $this->connection->isConnected();
    }

    public function connect()
    {
        $this->logger->notice('Trying to connect...');
       echo ('Trying to connect...')."\n";
        do {
            $this->logger->notice(sprintf('Try: %d', $this->triesDone + 1));
            echo (sprintf('Try: %d', $this->triesDone + 1))."\n";
            if ($this->triesDone > 0) {
                $delay = min((int) pow(2, $this->triesDone - 1), self::MAX_DELAY);
                $this->logger->notice(sprintf('Waiting %d seconds before issuing a new connection', $delay));
                echo (sprintf('Waiting %d seconds before issuing a new connection', $delay))."\n";
                sleep($delay);
            }

            $this->connection->close();
            $this->connection->connect();

            $this->ping();
            $this->triesDone++;
        } while (!$this->isConnected() && $this->hasRemainingTries());

        return $this->isConnected();
    }

    public function close()
    {
        $this->triesDone = 0;
        $this->connection->close();
        $this->isConnected = null;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($prepareString)
    {
        return new Statement($this->tryMethod(__FUNCTION__, func_get_args()), $this);
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

    public function __call($method, $args)
    {
        return $this->tryMethod($method, $args);
    }

    public static function isDeconnected(DBALException $e)
    {
        //@todo this would fail if it is not a PDO Connection
        while (null !== ($pdoException = $e->getPrevious()) && !$pdoException instanceof \PDOException);

        if (!($pdoException instanceof \PDOException)) {
            return false;
        }

        return
            ($pdoException->errorInfo[1] == self::MYSQL_CONNECTION_TIMED_WAIT_CODE) ||
            (false !== strpos($pdoException->getMessage(), 'MySQL server has gone away')) ||
            (false !== strpos($pdoException->getMessage(), 'Error while sending QUERY packet')) ||
            (false !== strpos($pdoException->getMessage(), 'errno=32 Broken pipe'));
    }

    private function ping()
    {
        $this->isConnected = null;

        if (false === $this->connection->isConnected()) {
            $this->isConnected = null;

            return;
        }

        try {
            $this->connection->query('SELECT 1');
            $this->logger->notice('Connection to database is established!');
            echo ('Connection to database is established!')."\n";
            $this->isConnected = true;
        } catch (DBALException $e) {
            $this->logger->notice('Failed to connect to database!');
            echo ('Failed to connect to database!')."\n";

            if (self::isDeconnected($e)) {
                $this->isConnected = false;
            }
        }
    }

    private function hasRemainingTries()
    {
        return $this->infiniteMode || (!$this->infiniteMode && ($this->triesDone < $this->tries));
    }

    private function tryMethod($method, $args)
    {
        try {
            return call_user_func_array([$this->connection, $method], $args);
        } catch (DBALException $e) {
            if (!self::isDeconnected($e)) {
                throw $e;
            }

            $this->logger->critical(sprintf('Database connection lost: %s', $e->getMessage()));
            echo "\n".(sprintf('Database connection lost: %s', $e->getMessage()))."\n";

            if (!$this->connect()) {
                $this->logger->critical('Failed to reconnect');
                echo ('Failed to reconnect')."\n";
                throw $e;
            }
        }

        return call_user_func_array([$this->connection, $method], $args);
    }
}
