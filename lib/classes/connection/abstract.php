<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

abstract class connection_abstract extends PDO
{
    protected $name;
    protected $credentials = [];
    protected $multi_db = true;

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
        try {
            $this->query('SELECT 1');
        } catch (PDOException $e) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param  string       $statement
     * @param  array        $driver_options
     * @return PDOStatement
     */
    public function prepare($statement, $driver_options = [])
    {
        return parent::prepare($statement, $driver_options);
    }

    /**
     *
     * @return boolean
     */
    public function beginTransaction()
    {
        return parent::beginTransaction();
    }

    /**
     *
     * @return boolean
     */
    public function commit()
    {
        return parent::commit();
    }

    /**
     *
     * @return string
     */
    public function server_info()
    {
        return parent::getAttribute(constant("PDO::ATTR_SERVER_VERSION"));
    }
}
