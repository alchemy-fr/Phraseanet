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

class module_report_sqlconnexion extends module_report_sql implements module_report_sqlReportInterface
{

    public function __construct(Application $app, module_report $report)
    {
        parent::__construct($app, $report);
    }

    public function buildSql()
    {
        $filter = $this->filter->getReportFilter() ? : ['params' => [], 'sql' => false];
        $this->params = array_merge([], $filter['params']);

        if ($this->groupby == false) {
            $this->sql = "SELECT\n"
                        . "  log.id,\n"
                        . "  log.user,\n"
                        . "  log.usrid,\n"
                        . "  log.date as ddate,\n"
                        . "  log.societe,\n"
                        . "  log.pays,\n"
                        . "  log.activite,\n"
                        . "  log.fonction,\n"
                        . "  log.site,\n"
                        . "  log.sit_session,\n"
                        . "  log.appli,\n"
                        . "  log.ip\n"
                        . "FROM log FORCE INDEX(date)\n"
                        . "WHERE (" . $filter['sql'] .") AND !ISNULL(log.usrid)\n";

            // file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n%s\n", __FILE__, __LINE__, var_export($this->sql, true), var_export($this->params, true)), FILE_APPEND);

            $stmt = $this->connbas->prepare($this->sql);
            $stmt->execute($this->params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();

            $this->sql .= $this->filter->getOrderFilter() ? : '';

            if ($this->enable_limit) {
                $this->sql .= $this->filter->getLimitFilter() ? : '';
            }
        } else {
            $this->sql = "SELECT " . $this->groupby . ", SUM(1) as nb\n"
                    . "FROM (\n"
                    . "  SELECT DISTINCT(log.id), TRIM(" . $this->getTransQuery($this->groupby) . ") AS " . $this->groupby . "\n"
                    . "  FROM log FORCE INDES(date)\n"
                    . "  WHERE (" . $filter['sql'] .")\n"
                    . ") AS tt\n"
                    . "GROUP BY " . $this->groupby. "\n"
                    . "ORDER BY nb DESC\n";

            // file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n%s\n", __FILE__, __LINE__, var_export($this->sql, true), var_export($this->params, true)), FILE_APPEND);

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

        $this->sql = "SELECT DISTINCT(val)\n"
                . "FROM (\n"
                . "  SELECT DISTINCT(log.id), " . $this->getTransQuery($field) . " AS val\n"
                . "  FROM log FORCE INDEX(date)\n"
                . "  WHERE (" . $filter['sql'] . ")\n"
                . ") AS tt\n"
                . "ORDER BY val ASC\n";

        // file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) %s\n%s\n", __FILE__, __LINE__, var_export($this->sql, true), var_export($this->params, true)), FILE_APPEND);

        return ['sql' => $this->sql, 'params' => $this->params];
    }
}
