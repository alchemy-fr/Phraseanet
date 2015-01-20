<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class connection_pdoStatementDebugger implements connection_statement
{
    protected $statement;
    protected $logger;

    public function __construct(\connection_statement $statement, Logger $logger = null)
    {
        $this->statement = $statement;
        $this->logger = $logger ?: new Logger('sql-query', array(new StreamHandler(__DIR__ . '/../../../logs/mysql_log.log')));

        return $this;
    }

    public function getQueryString()
    {
        return $this->statement->getQueryString();
    }

    public function execute($params = array())
    {
        $start = microtime(true);
        $exception = null;
        try {
            $result = $this->statement->execute($params);
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->logger->addInfo(sprintf(
            '%s sec - %s - %s',
            round(microtime(true) - $start, 4),
            $exception !== null ? 'ERROR QUERY' : 'OK QUERY',
            str_replace(array_keys($params), array_values($params), $this->getQueryString())
        ));

        if ($exception instanceof \Exception) {
            throw $exception;
        }

        return $result;
    }

    public function __call($function_name, $parameters)
    {
        return call_user_func_array(array($this->statement, $function_name), $parameters);
    }
}
