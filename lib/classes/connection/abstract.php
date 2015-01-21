<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class connection_abstract
{
    protected $name = null;
    protected $dsn = null;
    protected $credentials = array();
    protected $multi_db = true;
    protected $connection = null;

    abstract public function close();
    abstract public function connect();

    public function get_credentials()
    {
        return $this->credentials;
    }

    public function is_multi_db()
    {
        return $this->multi_db;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function ping()
    {
        if (false === $this->is_connected()) {
            return false;
        }

        try {
            $this->connection->query('SELECT 1');
        } catch (\PDOException $e) {
            return false;
        }

        return true;
    }

    public function server_info()
    {
        if (false === $this->ping()) {
            throw new \Exception('Mysql server is not reachable');
        }

        return $this->connection->getAttribute(constant("PDO::ATTR_SERVER_VERSION"));
    }

    public function supportInnoDB()
    {
        if (false === $this->ping()) {
            throw new \Exception('Mysql server is not reachable');
        }

        $stmt = $this->connection->query('SHOW ENGINES');
        $stmt->execute();
        $engines = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($engines as $engine) {
            if (strtolower($engine['Engine']) !== 'innodb') {
                continue;
            }

            return $engine['Support'] !== 'NO';
        }

        return false;
    }

    public function __destruct()
    {
        $this->close();
    }

    protected function is_connected()
    {
        return $this->connection !== null;
    }
}
