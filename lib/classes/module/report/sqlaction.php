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
class module_report_sqlaction extends module_report_sql implements module_report_sqlReportInterface
{
    private $action = 'add';

    public function __construct(Application $app, module_report $report)
    {
        parent::__construct($app, $report);
    }

    public function setAction($action)
    {
        //possible action
        $a = array('edit', 'add', 'push', 'validate');
        if (in_array($action, $a))
            $this->action = $action;

        return $this;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function buildSql()
    {
        if ($this->groupby == false) {
            $params = array(':action'    => $this->action);
            $site_filter = $this->filter->getGvSitFilter()? : array('params' => array(), 'sql'          => false);
            $report_filter = $this->filter->getReportFilter() ? : array('params' => array(), 'sql'          => false);
            $record_filter = $this->filter->getRecordFilter() ? : array('params' => array(), 'sql'   => false);
            $params = array_merge($params, $site_filter['params'], $report_filter['params'], $record_filter['params']);

            $this->sql =
                "
            SELECT log.usrid, log.user , d.final as getter,  d.record_id, d.date, s.*
            FROM (log_docs as d)
               INNER JOIN log ON (" . $site_filter['sql'] . " AND log.id = d.log_id)
               INNER JOIN log_colls ON (log.id = log_colls.log_id)
               INNER JOIN record ON (record.record_id = d.record_id)
               LEFT JOIN subdef as s ON (s.record_id=d.record_id and s.name='document')
            WHERE";

            $this->sql .= $report_filter['sql'] . " AND (d.action = :action)";

            $this->sql .= $record_filter['sql'] ? " AND (" . $record_filter['sql'] . ")" : "";

            $this->sql .= $this->filter->getOrderFilter();

            $stmt = $this->getConnBas()->prepare($this->sql);

            $this->total = $stmt->rowCount();
            $stmt->closeCursor();

            $this->sql .= $this->filter->getLimitFilter() ? : '';

            $this->params = $params;
        } else {

            $params = array(':action'    => $this->action);
            $site_filter = $this->filter->getGvSitFilter()? : array('params' => array(), 'sql'          => false);
            $report_filter = $this->filter->getReportFilter() ? : array('params' => array(), 'sql'          => false);
            $record_filter = $this->filter->getRecordFilter() ? : array('params' => array(), 'sql'   => false);
            $params = array_merge($params, $site_filter['params'], $report_filter['params'], $record_filter['params']);

            $this->sql = "
            SELECT TRIM(" . $this->getTransQuery($this->groupby) . ")
              as " . $this->groupby . ",
             SUM(1) as nombre
            FROM (log_docs as d)
                INNER JOIN log ON (" . $site_filter['sql'] . " AND log.id = d.log_id)
                INNER JOIN log_colls ON (log.id = log_colls.log_id)
                INNER JOIN record ON (record.record_id = d.record_id)
                LEFT JOIN subdef as s ON (s.record_id=d.record_id and s.name='document')
            WHERE ";

            $this->sql .= $report_filter['sql'] . " AND (d.action = :action)";

            $this->sql .= $record_filter['sql'] ? "AND (" . $record_filter['sql'] . ")" : "";

            $this->sql .= " GROUP BY " . $this->groupby;
            $this->sql .= $this->filter->getOrderFilter();

            $this->params = $params;

            $stmt = $this->getConnBas()->prepare($this->sql);
            $stmt->execute($params);
            $this->total = $stmt->rowCount();
            $stmt->closeCursor();
        }

        return $this;
    }

    public function sqlDistinctValByField($field)
    {

        $params = array();
        $site_filter = $this->filter->getGvSitFilter();
        $date_filter = $this->filter->getDateFilter();

        $params = array_merge($params, $site_filter['params'], $date_filter['params']); //, $record_filter ? $record_filter['params'] : array()

        $sql = "
            SELECT  DISTINCT(" . $this->getTransQuery($field) . ") as val
            FROM (log_docs as d)
                INNER JOIN log ON (" . $site_filter['sql'] . "
                    AND log.id = d.log_id
                    AND " . $date_filter['sql'] . ")
                INNER JOIN log_colls ON (log.id = log_colls.log_id)
                INNER JOIN record ON (record.record_id = d.record_id)
                LEFT JOIN subdef as s ON (s.record_id=d.record_id AND s.name='document')
            WHERE ";

        if ($this->filter->getReportFilter()) {
            $report_filter = $this->filter->getReportFilter();
            $sql .= $report_filter['sql'] . " AND (d.action = :action)";
            $params = array_merge($params, $report_filter['params'], array(':action' => $this->action));
        }
        $this->sql .= $this->filter->getOrderFilter();
        $this->params = $params;

        return array('sql'    => $sql, 'params' => $params);
    }
}
