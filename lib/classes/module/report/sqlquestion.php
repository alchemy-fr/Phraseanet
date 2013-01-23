<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class module_report_sqlquestion extends module_report_sql implements module_report_sqlReportInterface
{

    public function __construct(Application $app, module_report $report)
    {
        parent::__construct($app, $report);
    }

    public function buildSql()
    {
        $filter = $this->filter->getReportFilter() ? : array('params' => array(), 'sql'         => false);
        $this->params = array_merge(array(), $filter['params']);

        if ($this->groupby == false) {
            $this->sql ="
                SELECT DISTINCT(log.id), log_search.date ddate, log_search.search, log.usrid, log.user, log.pays, log.societe, log.activite, log.fonction
                FROM log_search
                INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_search.log_id)
                INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id) WHERE (" . $filter['sql'] .")";

            $stmt = $this->connbas->prepare($this->sql);
            $stmt->execute($this->params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();

            $this->sql .= $this->filter->getOrderFilter() ? : '';
            $this->sql .= $this->filter->getLimitFilter() ? : '';
        } else {
            $this->sql = "
                SELECT " . $this->groupby . ", SUM(1) AS nb
                FROM (
                    SELECT DISTINCT(log.id), TRIM(" . $this->getTransQuery($this->groupby) . ") AS " . $this->groupby . "
                    FROM (`log_search`)
                    INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_search.log_id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                    WHERE (" . $filter['sql'] .")
                ) AS tt
                GROUP BY " . $this->groupby ."
                ORDER BY nb DESC";

            $stmt = $this->connbas->prepare($this->sql);
            $stmt->execute($this->params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();
        }

        return $this;
    }

    public function sqlDistinctValByField($field)
    {
        $filter = $this->filter->getReportFilter() ? : array('params' => array(), 'sql'         => false);
        $this->params = array_merge(array(), $filter['params']);

        $this->sql = "
            SELECT DISTINCT(tt.val)
            FROM (
                SELECT DISTINCT(log.id), " . $this->getTransQuery($field) . " AS val
                FROM (`log_search`)
                INNER JOIN log FORCE INDEX (date_site) ON (log.id = log_search.log_id)
                INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                WHERE (" . $filter['sql'] . ")
            ) as tt
            ORDER BY tt.val ASC";

        return array('sql'    => $this->sql, 'params' => $this->params);
    }
}
