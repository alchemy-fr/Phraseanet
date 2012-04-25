<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
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

    public function execute($params = array())
    {
        $start = microtime(true);
        $exception = null;
        try {
            $result = $this->statement->execute($params);
        } catch (Exception $e) {
            $exception = $e;
        }
        $time = microtime(true) - $start;
        connection::$log[] = array(
            'query' => '' . str_replace(array_keys($params), array_values($params), $this->statement->queryString),
            'time'  => $time
        );
        if ($exception instanceof Exception)
            throw $exception;

        return $result;
    }

    public function __call($function_name, $parameters)
    {
        return call_user_func_array(array($this->statement, $function_name), $parameters);
    }
}
