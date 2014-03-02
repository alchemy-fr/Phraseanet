<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     connection
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class connection_pdo extends connection_abstract implements connection_interface
{
    protected $debug;

    /**
     *
     * @param string  $name
     * @param string  $hostname
     * @param int     $port
     * @param string  $user
     * @param string  $passwd
     * @param string  $dbname
     * @param array   $options
     * @param Boolean $debug
     *
     * @return connection_pdo
     */
    public function __construct($name, $hostname, $port, $user, $passwd, $dbname = false, $options = array(), $debug = false)
    {
        $this->debug = $debug;
        $this->name = $name;

        $this->credentials['hostname'] = $hostname;
        $this->credentials['port'] = $port;
        $this->credentials['user'] = $user;
        $this->credentials['password'] = $passwd;

        if ($dbname)
            $this->credentials['dbname'] = $dbname;

        $this->initConn();

        return $this;
    }

    protected function initConn()
    {
        $this->connection = null;

        if (isset($this->credentials['dbname']))
            $dsn = 'mysql:dbname=' . $this->credentials['dbname'] . ';host=' . $this->credentials['hostname'] . ';port=' . $this->credentials['port'] . ';';
        else
            $dsn = 'mysql:host=' . $this->credentials['hostname'] . ';port=' . $this->credentials['port'] . ';';

        $this->connection = new \PDO($dsn, $this->credentials['user'], $this->credentials['password'], array());

        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->exec("
            SET character_set_results = 'utf8', character_set_client = 'utf8',
            character_set_connection = 'utf8', character_set_database = 'utf8',
            character_set_server = 'utf8'");
    }

    /**
     *
     * @return void
     */
    public function disconnect()
    {
        $this->connection = null;
    }

    /**
     *
     * @return void
     */
    public function close()
    {
        connection::close_PDO_connection($this->name);
    }

    public function __call($method, $args)
    {
        if (null === $this->connection) {
            $this->initConn();
        }

        if (!method_exists($this->connection, $method)) {
            throw new \BadMethodCallException(sprintf('Method %s does not exist', $method));
        }

        $tries = 0;

        do {
            $tries++;
            try {
                set_error_handler(function ($errno, $errstr) {
                    if (false !== strpos($errstr, 'Error while sending QUERY packet')) {
                        throw new \Exception('MySQL server has gone away');
                    }
                    throw new \Exception($errstr);
                });

                if ('prepare' === $method && $this->debug) {
                    $ret = new connection_pdoStatementDebugger(call_user_func_array(array($this->connection, $method), $args));
                } else {
                    $ret = call_user_func_array(array($this->connection, $method), $args);
                }
                restore_error_handler();

                return $ret;
            } catch (\Exception $e) {
                restore_error_handler();

                $found = (false !== strpos($e->getMessage(), 'MySQL server has gone away')) || (false !== strpos($e->getMessage(), 'errno=32 Broken pipe'));
                if ($tries >= 2 || !$found) {
                    throw $e;
                }
                $this->initConn();
            }
        } while (true);
    }

    /**
     *
     * @param  string         $message
     * @return connection_pdo
     */
    protected function log($message)
    {
        file_put_contents(__DIR__ . '/../../../logs/sql_log.log', $message . "\n", FILE_APPEND);

        return $this;
    }
}
