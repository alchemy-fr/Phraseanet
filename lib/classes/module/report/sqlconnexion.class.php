<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     module_report
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class module_report_sqlconnexion extends module_report_sql implements module_report_sqlReportInterface
{

    public function __construct(module_report $report)
    {
        parent::__construct($report);
    }

    public function buildSql()
    {
        $report_filter = $this->filter->getReportFilter();
        $params = $report_filter['params'];

        $this->params = $params;
        if ($this->groupby == false) {
            $this->sql = "
       SELECT
        user,
        usrid,
        log.date as ddate,
        log.societe,
        log.pays,
        log.activite,
        log.fonction,
        site,
        sit_session,
        coll_list,
        appli,
        ip
       FROM log";

            $this->sql .= " WHERE " . $report_filter['sql'];
            $this->sql .= $this->filter->getOrderFilter() ? : '';


            $stmt = $this->connbas->prepare($this->sql);
            $stmt->execute($params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();
            if ($this->enable_limit)
                $this->sql .= $this->filter->getLimitFilter() ? : '';
        }
        else {
            $this->sql = "
       SELECT  TRIM(" . $this->getTransQuery($this->groupby) . ")
              as " . $this->groupby . ", SUM(1) as nb
       FROM  log  ";

            if ($report_filter['sql'])
                $this->sql .= " WHERE " . $report_filter['sql'];

            $this->sql .= " GROUP BY " . $this->groupby;
            $this->sql .= $this->filter->getOrderFilter() ? : '';



            $stmt = $this->connbas->prepare($this->sql);
            $stmt->execute($params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();
        }

        return $this;
    }

    public function sqlDistinctValByField($field)
    {
        $report_filter = $this->filter->getReportFilter();
        $params = $report_filter['params'];

        $sql = 'SELECT  DISTINCT(' . $this->getTransQuery($field) . ') as val
            FROM  log ';

        if ($report_filter['sql'])
            $sql .= ' WHERE ' . $report_filter['sql'];

        $sql .= ' ORDER BY val ASC';

        return array('sql'    => $sql, 'params' => $params);
    }
}
