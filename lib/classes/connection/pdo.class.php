<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
     * @param  string         $name
     * @param  string         $hostname
     * @param  int            $port
     * @param  string         $user
     * @param  string         $passwd
     * @param  string         $dbname
     * @param  array          $options
     * @return connection_pdo
     */
    public function __construct($name, $hostname, $port, $user, $passwd, $dbname = false, $options = array(), $debug = false)
    {
        $this->debug = $debug;
        $this->name = $name;
        if ($dbname)
            $dsn = 'mysql:dbname=' . $dbname . ';host=' . $hostname . ';port=' . $port . ';';
        else
            $dsn = 'mysql:host=' . $hostname . ';port=' . $port . ';';

        $this->credentials['hostname'] = $hostname;
        $this->credentials['port'] = $port;
        $this->credentials['user'] = $user;
        $this->credentials['password'] = $passwd;
        if ($dbname)
            $this->credentials['dbname'] = $dbname;

        parent::__construct($dsn, $user, $passwd, $options);

        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->query("
      SET character_set_results = 'utf8', character_set_client = 'utf8',
      character_set_connection = 'utf8', character_set_database = 'utf8',
      character_set_server = 'utf8'");

        return $this;
    }

    /**
     *
     * @param  type         $statement
     * @param  type         $driver_options
     * @return PDOStatement
     */
    public function prepare($statement, $driver_options = array())
    {
        if ($this->debug) {
            return new connection_pdoStatementDebugger(parent::prepare($statement, $driver_options));
        } else {
            return parent::prepare($statement, $driver_options);
        }
    }

    /**
     *
     * @return void
     */
    public function close()
    {
        connection::close_PDO_connection($this->name);
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
