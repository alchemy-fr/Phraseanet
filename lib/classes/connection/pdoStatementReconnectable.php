<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class connection_pdoStatementReconnectable implements \connection_statement
{
    protected $queryString;
    protected $statement;
    protected $conn;

    public function __construct(\connection_statement $statement, \connection_pdo $conn)
    {
        $this->statement = $statement;
        $this->conn = $conn;

        return $this;
    }

    public function getQueryString()
    {
        return $this->statement->getQueryString();
    }

    public function execute($params = array())
    {
        try {
            return $this->statement->execute($params);
        } catch (\Exception $e) {
            $unreachable = ($e->getCode() === 2006) || (false !== strpos($e->getMessage(), 'MySQL server has gone away')) || (false !== strpos($e->getMessage(), 'errno=32 Broken pipe'));
            if (!$unreachable) {
                throw $e;
            }
            $this->conn->connect();
        }
        // retry query with update statement
        $this->statement = $this->conn->prepare($this->getQueryString());

        return $this->statement->execute($params);
    }

    public function __call($function_name, $parameters)
    {
        return call_user_func_array(array($this->statement, $function_name), $parameters);
    }
}
