<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class connection_pdoStatementDebugger
{
    /**
     *
     * @var PDOStatement
     */
    protected $statement;

    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;

        return $this;
    }

    public function execute($params = [])
    {
        $start = microtime(true);
        $exception = null;
        try {
            $result = $this->statement->execute($params);
        } catch (\Exception $e) {
            $exception = $e;
        }
        $time = microtime(true) - $start;
        connection::$log[] = [
            'query' => '' . str_replace(array_keys($params), array_values($params), $this->statement->queryString),
            'time'  => $time
        ];
        if ($exception instanceof Exception)
            throw $exception;

        return $result;
    }

    public function __call($function_name, $parameters)
    {
        return call_user_func_array([$this->statement, $function_name], $parameters);
    }
}
