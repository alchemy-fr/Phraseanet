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

class ReportCountAssets extends Report
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
        $stmt = $this->databox->get_connection()->executeQuery($this->sql, []);
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

        $sqlWhereDate = " date>="  . $this->databox->get_connection()->quote($this->parms['dmin']);

        if ($this->parms['dmax']) {
            $sqlWhereDate .= " AND date<=" . $this->databox->get_connection()->quote($this->parms['dmax'] . " 23:59:59");
        }

        switch($this->parms['group']) {
            case 'added,year':
                $this->name = "Number of added assets per year";
                $this->columnTitles = ['year', 'nb'];
                $this->sql = "SELECT YEAR(date) AS year,COUNT(record_id) AS nb \n"
                        . "FROM log_docs WHERE action='add' AND " . $sqlWhereDate ."\n"
                        . "GROUP BY year";

                break;
            case 'added,year,month':
                $this->name = "Number of added assets per year, month";
                $this->columnTitles = ['year', 'month', 'nb'];
                $this->sql = "SELECT YEAR(date) AS year, MONTH(date) AS month, COUNT(record_id) AS nb \n"
                    . "FROM log_docs WHERE action='add' AND " . $sqlWhereDate ."\n"
                    . "GROUP BY year, month";

                break;
            case 'downloaded,year':
                $this->name = "Number of downloaded assets per year";
                $this->columnTitles = ['year', 'nb'];
                $this->sql = "SELECT YEAR(date) AS year, COUNT(record_id) AS nb \n"
                    . "FROM log_docs WHERE (action='download' OR action='mail') AND "  . $sqlWhereDate ."\n"
                    . "GROUP BY year";

                break;
            case 'downloaded,year,month':
                $this->name = "Number of downloaded assets per year, month";
                $this->columnTitles = ['year', 'month', 'nb'];
                $this->sql = "SELECT YEAR(date) AS year, MONTH(date) AS month, COUNT(record_id) AS nb \n"
                    . "FROM log_docs WHERE (action='download' OR action='mail') AND "  . $sqlWhereDate ."\n"
                    . "GROUP BY year, month";

                break;
            case 'downloaded,year,month,action':
                $this->name = "Number of downloaded assets per year, month, action";
                $this->columnTitles = ['action', 'year', 'month', 'nb'];
                $this->sql = "SELECT action, YEAR(date) AS year, MONTH(date) AS month, COUNT(record_id) AS nb \n"
                    . "FROM log_docs WHERE (action='download' OR action='mail') AND "  . $sqlWhereDate ."\n"
                    . "GROUP BY year, month, action";

                break;
            case 'most-downloaded':
                $this->name = "Most downloaded assets";
                $this->columnTitles = ['record_id', 'nb'];
                $this->sql = "SELECT record_id, COUNT(record_id) AS nb \n"
                    . "FROM log_docs WHERE (action='download' OR action='mail') AND "  . $sqlWhereDate ."\n"
                    . "GROUP BY record_id ORDER BY nb DESC limit 20";

                break;
            default:
                throw new InvalidArgumentException('invalid "group" argument');
                break;
        }
    }
}
