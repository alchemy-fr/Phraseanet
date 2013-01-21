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

/**
 *
 * @package     module_report
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_report_sqlquestion extends module_report_sql implements module_report_sqlReportInterface
{

    public function __construct(Application $app, module_report $report)
    {
        parent::__construct($app, $report);
    }

    public function buildSql()
    {
        $params = array();
        $report_filter = $this->filter->getReportFilter();
        $params = array_merge($params, $report_filter['params']);
        $this->params = $params;

        if ($this->groupby == false) {
            $this->sql =
                "
       SELECT
        log_search.date ddate,
        search,
        usrid,
        user,
        pays,
        societe,
        activite,
        fonction
       FROM `log_search`
       INNER JOIN log
       ON log.id = log_search.log_id
       ";

            $this->sql .= " WHERE " . $report_filter['sql'];

            $this->sql .= $this->filter->getOrderFilter() ? : '';

            $stmt = $this->connbas->prepare($this->sql);
            $stmt->execute($params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();

            $this->sql .= $this->filter->getLimitFilter() ? : '';
        } else {
            $this->sql = "
         SELECT
          TRIM(" . $this->getTransQuery($this->groupby) . ") as " . $this->groupby . ",
          SUM(1) as nb
         FROM `log_search`
         INNER JOIN log
         ON log.id = log_search.log_id
         ";

            $this->sql .= " WHERE " . $report_filter['sql'];
            $this->sql .= " GROUP BY " . $this->groupby;
            $this->sql .= " ORDER BY nb DESC";

            $stmt = $this->connbas->prepare($this->sql);
            $stmt->execute($params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();
        }

        return $this;
    }

    public function sqlDistinctValByField($field)
    {
        $params = array();
        $report_filter = $this->filter->getReportFilter();
        $params = array_merge($params, $report_filter['params']);

        $sql = "
            SELECT DISTINCT(" . $this->getTransQuery($field) . ") as val
            FROM `log_search`
            INNER JOIN log
            ON log.id = log_search.log_id
            ";

        if ($report_filter['sql'])
            $sql .= ' WHERE ' . $report_filter['sql'];

        $sql .= " ORDER BY " . $this->getTransQuery($field) . " ASC";

        return array('sql'    => $sql, 'params' => $params);
    }
}

