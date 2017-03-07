<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class module_report_sqlquestion extends module_report_sql implements module_report_sqlReportInterface
{
    public function __construct(Application $app, module_report $report)
    {
        $report->setDateField('log_search.date');
        parent::__construct($app, $report);
    }

    public function buildSql()
    {
        $filter = $this->filter->getReportFilter() ? : ['params' => [], 'sql' => false];
        $this->params = array_merge([], $filter['params']);

        if ($this->groupby == false) {
            $this->sql ="SELECT log.id, log_search.date ddate, log_search.search, log.usrid, log.user, log.pays, log.societe, log.activite, log.fonction\n"
                    . "FROM log_search INNER JOIN log ON log.id = log_search.log_id AND !ISNULL(usrid)\n"
                    . "WHERE (" . $filter['sql'] .")\n";

            $stmt = $this->connbas->prepare($this->sql);
            $stmt->execute($this->params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();

            $this->sql .= $this->filter->getOrderFilter() ? : '';

            if ($this->enable_limit) {
                $this->sql .= $this->filter->getLimitFilter() ? : '';
            }
        } else {
            $this->sql = "SELECT " . $this->groupby . ", SUM(1) AS nb\n"
                        . "FROM (\n"
                        . "  SELECT DISTINCT(log.id), TRIM(" . $this->getTransQuery($this->groupby) . ") AS " . $this->groupby . "\n"
                        . "  FROM log_search INNER JOIN log ON log.id = log_search.log_id\n"
                        . "  WHERE (" . $filter['sql'] . ")\n"
                        . ") AS tt\n"
                        . "GROUP BY " . $this->groupby . "\n"
                        . "ORDER BY nb DESC\n";

            $stmt = $this->connbas->prepare($this->sql);
            $stmt->execute($this->params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();
        }

        return $this;
    }

    public function sqlDistinctValByField($field)
    {
        $filter = $this->filter->getReportFilter() ? : ['params' => [], 'sql' => false];
        $this->params = array_merge([], $filter['params']);

        $this->sql = "SELECT DISTINCT(tt.val)\n"
                    . "FROM (\n"
                    . "  SELECT DISTINCT(log.id), " . $this->getTransQuery($field) . " AS val\n"
                    . "  FROM log_search INNER JOIN log ON log.id = log_search.log_id\n"
                    . "  WHERE (" . $filter['sql'] . ")\n"
                    . ") as tt\n"
                    . "ORDER BY tt.val ASC\n";

        return ['sql' => $this->sql, 'params' => $this->params];
    }
}
