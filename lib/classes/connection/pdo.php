<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class connection_pdo extends connection_abstract implements connection_interface
{
    protected $debug;
    protected $retryFrequency = 2;
    protected $retryNumber = 1;

    public function __construct($name, $hostname, $port, $user, $password, $databaseName = false, $options = array(), $debug = false)
    {
        $this->debug = $debug;
        $this->name = $name;
        $this->credentials['hostname'] = $hostname;
        $this->credentials['port'] = $port;
        $this->credentials['user'] = $user;
        $this->credentials['password'] = $password;
        if ($databaseName) {
            $this->credentials['dbname'] = $databaseName;
        }

        $this->dsn = $this->buildDataSourceName($hostname, $port, $databaseName);

        $this->connect();

        return $this;
    }

    public function setRetryNumber($retryNumber)
    {
        $this->retryNumber = $retryNumber;
    }

    public function setRetryFrequency($retryFrequency)
    {
        $this->retryFrequency = $retryFrequency;
    }

    public function connect()
    {
        // already connected do not reconnect
        if ($this->ping()) {
            return;
        }

        // if disconnected close connection
        $this->close();

        $tries = $this->retryNumber;
        $infiniteMode = $this->retryNumber <= 0;
        $lastException = null;

        do {
            if (!$infiniteMode) {
                $tries--;
            }
            try {
                $this->init();
            } catch (\PDOException $e) {
                $this->connection = null;
                $lastException = $e;

                // wait only if there is at least one try remaining or in infinite mode
                // && connection has not been initialized
                if ($infiniteMode || (!$infiniteMode && $tries !== 0)) {
                    sleep($this->retryFrequency);
                }
            }
        } while (!$this->is_connected() && ($infiniteMode || (!$infiniteMode && $tries > 0)));

        if (!$this->is_connected()) {
            throw new Exception(sprintf('Failed to connect to "%s" database', $this->dsn), 0, $lastException);
        }
    }

    public function close()
    {
        $this->connection = null;
    }

    public function __call($method, $args)
    {
        $this->init();

        if (!method_exists($this->connection, $method)) {
            throw new \BadMethodCallException(sprintf('Method %s does not exist', $method));
        }

        try {
            set_error_handler(function ($errno, $errstr) {
                if (false !== strpos($errstr, 'Error while sending QUERY packet')) {
                    throw new \Exception('MySQL server has gone away');
                }
                throw new \Exception($errstr);
            });

            $returnValue = $this->doMethodCall($method, $args);

            restore_error_handler();

            return $returnValue;
        } catch (\Exception $e) {
            restore_error_handler();

            $unreachable = (false !== strpos($e->getMessage(), 'MySQL server has gone away')) || (false !== strpos($e->getMessage(), 'errno=32 Broken pipe'));
            if (!$unreachable) {
                throw $e;
            }

            $this->connect();
        }

        return $this->doMethodCall($method, $args);
    }

    protected function init()
    {
        if ($this->is_connected()) {
            return;
        }

        $this->connection = new \PDO($this->dsn, $this->credentials['user'], $this->credentials['password'], array());

        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->connection->exec("
            SET character_set_results = 'utf8', character_set_client = 'utf8',
            character_set_connection = 'utf8', character_set_database = 'utf8',
            character_set_server = 'utf8'
        ");
    }

    private function doMethodCall($method, $args)
    {
        if ('prepare' === $method) {
            $pdoStatement = call_user_func_array(array($this->connection, $method), $args);

            $statement = new connection_pdoStatement($pdoStatement);

            // decorate statement with reconnectable statement
            $statement = new connection_pdoStatementReconnectable($statement, $this);

            if ($this->debug) {
                // decorate reconnectable statement with debugger one
                $statement = new connection_pdoStatementDebugger($statement);
            }

           return $statement;
        }

        return call_user_func_array(array($this->connection, $method), $args);
    }

    private function buildDataSourceName($host, $port, $databaseName = null)
    {
        if (isset($databaseName)) {
            return sprintf('mysql:host=%s;port=%s;dbname=%s;', $host, $port, $databaseName);
        }

        return sprintf('mysql:host=%s;port=%s;', $host, $port);
    }
}
