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

use Alchemy\Phrasea\Exception\InvalidArgumentException;

class ReportUsers extends Report
{
    /* those vars will be set once by computeVars() */
    private $name = null;
    private $sql = null;
    private $columnTitles = [];
    private $keyName = null;

    public function getName()
    {
        $this->computeVars();

        return $this->name;
    }

    public function getColumnTitles()
    {
        $this->computeVars();

        return $this->columnTitles;
    }

    public function getKeyName()
    {
        $this->computeVars();

        return $this->keyName;
    }

    public function getAllRows($callback)
    {
        $this->computeVars();
        // use appbox connection
        $stmt = $this->databox->get_appbox()->get_connection()->executeQuery($this->sql, []);
        while (($row = $stmt->fetch())) {
            $callback($row);
        }
        $stmt->closeCursor();
    }

    private function computeVars()
    {
        if(!is_null($this->name)) {
            // vars already computed
            return;
        }
        $sqlWhereDate = " created>="  . $this->databox->get_appbox()->get_connection()->quote($this->parms['dmin']);

        if ($this->parms['dmax']) {
            $sqlWhereDate .= " AND created<=" . $this->databox->get_appbox()->get_connection()->quote($this->parms['dmax'] . " 23:59:59");
        }

        switch($this->parms['group']) {
            case 'added,year':
                $this->name = "Number of newly added user per year";
                $this->columnTitles = ['year', 'nb'];
                $this->sql = "SELECT YEAR(created) AS year, COUNT(id) AS nb \n "
                        . "FROM Users \n"
                        . "WHERE ". $sqlWhereDate . "\n"
                        . "GROUP BY year";
                break;
            case 'added,year,month':
                $this->name = "Number of newly added user per year,month";
                $this->columnTitles = ['year', 'month', 'nb'];
                $this->sql = "SELECT YEAR(created) AS year, MONTH(created) AS month, COUNT(id) AS nb \n "
                    . "FROM Users \n"
                    . "WHERE ". $sqlWhereDate . "\n"
                    . "GROUP BY year, month";
                break;
            default:
                throw new InvalidArgumentException('invalid "group" argument');
                break;
        }
    }
}
