<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Connection;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Statement as DoctrineStatement;

class Statement extends DoctrineStatement
{
    protected $stmt;
    protected $connection;

    private $values;

    public function __construct(DoctrineStatement $stmt, ReconnectableConnection $connection)
    {
        $this->stmt = $stmt;
        $this->connection = $connection;
        $this->values = array();
    }

    public function execute($params = null)
    {
        try {
            return $this->stmt->execute($params);
        } catch (DBALException $e) {
            if (!ReconnectableConnection::isDeconnected($e)) {
                throw $e;
            }
            // connect
            if (!$this->connection->connect()) {
                throw $e;
            }
        }

        // retry query with updated connection
        $this->stmt = $this->connection->prepare($this->stmt->getWrappedStatement()->queryString);
        // re bind values
        foreach ($this->values as $value) {
            $this->stmt->bindValue($value['param'], $value['value'], $value['type']);
        }

        return $this->stmt->execute($params);
    }

    public function bindValue($param, $value, $type = null)
    {
        $this->values[] = array('param' => $param, 'value' => $value, 'type' => $type,);

        return call_user_func_array(array($this->stmt, __FUNCTION__), func_get_args());
    }

    public function __call($function, $parameters)
    {
        return call_user_func_array(array($this->stmt, $function), $parameters);
    }
}
