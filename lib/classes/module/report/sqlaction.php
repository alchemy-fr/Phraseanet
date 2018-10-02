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

class module_report_sqlaction extends module_report_sql implements module_report_sqlReportInterface
{
    private $action = 'add';

    public function __construct(Application $app, module_report $report)
    {
        parent::__construct($app, $report);
    }

    public function setAction($action)
    {
        $a = ['edit', 'add', 'push', 'validate', 'mail'];

        if (in_array($action, $a)) {
            $this->action = $action;
        }

        return $this;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function buildSql()
    {
        $customFieldMap = [];

        $filter = $this->filter->getReportFilter() ? : ['params' => [], 'sql' => false];
        $this->params = array_merge([':action' => $this->action], $filter['params']);

        if ($this->groupby == false) {
            $this->sql = "
                SELECT tt.usrid, tt.user, tt.final AS getter, tt.record_id, tt.date, tt.mime, tt.file, tt.comment
                FROM (
                    SELECT DISTINCT(log.id), log.usrid, log.user, d.final, d.comment, d.record_id, d.date, record.mime, record.originalname as file
                    FROM (log_docs AS d)
                    INNER JOIN log FORCE INDEX (date_site) ON (log.id = d.log_id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                    INNER JOIN record ON (record.record_id = d.record_id)
                    WHERE (" . $filter['sql'] . ") AND (d.action = :action)
                ) AS tt";

            $customFieldMap = [
                'log.usrid'     => 'tt.usrid',
                'log.user'      => 'tt.user',
                'd.final'       => 'getter',
                'd.record_id'   => 'tt.record_id',
                'd.date'        => 'tt.date',
                'record.mime'   => 'tt.mime',
                'file'          => 'tt.file',
                'd.comment'     => 'tt.comment'
            ];

            $stmt = $this->getConnBas()->prepare($this->sql);
            $stmt->execute($this->params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();

            $this->sql .= $this->filter->getOrderFilter($customFieldMap) ? : '';
            $this->sql .= $this->filter->getLimitFilter() ? : '';
        } else {
            $this->sql = "
                SELECT " . $this->groupby . ", SUM(1) AS nombre
                FROM (
                    SELECT DISTINCT(log.id), TRIM(" . $this->getTransQuery($this->groupby) . ") AS " . $this->groupby . " , log.usrid , d.final,  d.record_id, d.date
                    FROM (log_docs as d)
                        INNER JOIN log FORCE INDEX (date_site) ON (log.id = d.log_id)
                        INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                        INNER JOIN record ON (record.record_id = d.record_id)
                        WHERE (" . $filter['sql'] . ") AND (d.action = :action)
                ) AS tt
                LEFT JOIN subdef AS s ON (s.record_id=tt.record_id)
                WHERE s.name='document'
                GROUP BY " . $this->groupby . "
                ORDER BY nombre";

            $stmt = $this->getConnBas()->prepare($this->sql);
            $stmt->execute($this->params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();
        }

        return $this;
    }

    public function sqlDistinctValByField($field)
    {
        $filter = $this->filter->getReportFilter() ? : ['params' => [], 'sql' => false];
        $this->params = array_merge([':action' => $this->action], $filter['params']);

        $this->sql = "
            SELECT DISTINCT(val)
            FROM (
                SELECT DISTINCT(log.id), " . $this->getTransQuery($field) . " AS val
                FROM (log_docs as d)
                    INNER JOIN log FORCE INDEX (date_site) ON (log.id = d.log_id)
                    INNER JOIN log_colls FORCE INDEX (couple) ON (log.id = log_colls.log_id)
                    INNER JOIN record ON (record.record_id = d.record_id)
                    LEFT JOIN subdef as s ON (s.record_id=d.record_id AND s.name='document')
                WHERE (" . $filter['sql'] . ")
                AND (d.action = :action)
            ) AS tt " . ($this->filter->getOrderFilter() ? $this->filter->getOrderFilter() : '');

        return ['sql' => $this->sql, 'params' => $this->params];
    }
}
