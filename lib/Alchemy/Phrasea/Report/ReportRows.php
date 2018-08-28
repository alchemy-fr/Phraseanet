<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Report;

use Alchemy\Phrasea\Application;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;



class ReportRows implements \Iterator
{
    private $row;

    /** @var Statement */
    private $stmt = null;

    private $connection;
    private $sql;
    private $sqlParms;
    private $keyName;

    public function __construct(Connection $connection, $sql, $sqlParms, $keyName = 'id')
    {
        $this->connection = $connection;
        $this->sql = $sql;
        $this->sqlParms = $sqlParms;
        $this->keyName = $keyName;
    }

    public function __destruct()
    {
        if(!is_null($this->stmt)) {
            $this->stmt->closeCursor();
        }
    }

    public function rewind()
    {
        if(!is_null($this->stmt)) {
            $this->stmt->closeCursor();
        }
        $this->stmt = $this->connection->prepare($this->sql);
        $this->stmt->execute($this->sqlParms);
        $this->next();
    }

    public function valid()
    {
        return !!($this->row);
    }

    public function current()
    {
        return $this->row;
    }

    public function key()
    {
        return $this->row ? $this->row[$this->keyName] : null;
    }

    public function next()
    {
        $this->row = $this->stmt->fetch();
    }
}
