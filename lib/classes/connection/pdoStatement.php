<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class connection_pdoStatement implements \connection_statement
{
    protected $statement;

    public function __construct(\PDOStatement $statement)
    {
        $this->statement = $statement;

        return $this;
    }

    public function getQueryString()
    {
        return $this->statement->queryString;
    }

    public function execute($params = array())
    {
        return $this->statement->execute($params);
    }

    public function __call($function_name, $parameters)
    {
        return call_user_func_array(array($this->statement, $function_name), $parameters);
    }
}
