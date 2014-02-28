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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class connection_abstract
{
    protected $name;
    protected $credentials = array();
    protected $multi_db = true;
    protected $connection;

    public function get_credentials()
    {
        return $this->credentials;
    }

    public function is_multi_db()
    {
        return $this->multi_db;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    public function ping()
    {
        if (null === $this->connection) {
            $this->initConn();
        }

        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            return false;
        }

        return true;
    }

    /**
     *
     * @return string
     */
    public function server_info()
    {
        if (null === $this->connection) {
            $this->initConn();
        }

        return $this->connection->getAttribute(constant("PDO::ATTR_SERVER_VERSION"));
    }
}
