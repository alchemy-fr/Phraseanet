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
        $report->setDateField('log_docs.date');
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
            $this->sql = "SELECT tt.usrid, tt.user, tt.final AS getter, tt.record_id, tt.date, tt.mime, tt.file, tt.comment\n"
                    . "FROM (\n"
                    . "  SELECT DISTINCT(log.id), log.usrid, log.user,\n"
                    . "         log_docs.final, log_docs.comment, log_docs.record_id, log_docs.date,\n"
                    . "         record.mime, record.originalname AS file\n"
                    . "  FROM (log_docs INNER JOIN log ON log.id = log_docs.log_id)\n"
                    . "    LEFT JOIN record ON record.record_id = log_docs.record_id\n"
                    . "  WHERE (" . $filter['sql'] . ")\n"
                    . "    AND log_docs.action = :action\n"
                    . ") AS tt\n";

            $customFieldMap = [
                'log.usrid'          => 'tt.usrid',
                'log.user'           => 'tt.user',
                'log_docs.final'     => 'getter',
                'log_docs.record_id' => 'tt.record_id',
                'log_docs.date'      => 'tt.date',
                'record.mime'        => 'tt.mime',
                'file'               => 'tt.file',
                'log_docs.comment'   => 'tt.comment'
            ];

            $stmt = $this->getConnBas()->prepare($this->sql);
            $stmt->execute($this->params);
            $this->total_row = $stmt->rowCount();
            $stmt->closeCursor();

            $this->sql .= $this->filter->getOrderFilter($customFieldMap) ? : '';
            $this->sql .= $this->filter->getLimitFilter() ? : '';
        } else {
            $this->sql = "SELECT " . $this->groupby . ", SUM(1) AS nombre\n"
                        . "FROM (\n"
                        . "  SELECT DISTINCT(log.id), TRIM(" . $this->getTransQuery($this->groupby) . ") AS " . $this->groupby . ",\n"
                        . "         log.usrid, log_docs.final, log_docs.record_id, log_docs.date\n"
                        . "  FROM (log_docs INNER JOIN log ON log.id = log_docs.log_id)\n"
                        . "    LEFT JOIN record ON record.record_id = log_docs.record_id\n"
                        . "  WHERE (" . $filter['sql'] . ") AND (log_docs.action = :action)\n"
                        . ") AS tt\n"
                        . "LEFT JOIN subdef AS s ON (s.record_id=tt.record_id)\n"
                        . "WHERE s.name='document'\n"
                        . "GROUP BY " . $this->groupby . "\n"
                        . "ORDER BY nombre\n";

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

        $this->sql = "SELECT DISTINCT(val)\n"
                . "FROM (\n"
                . "  SELECT DISTINCT(log.id), " . $this->getTransQuery($field) . " AS val\n"
                . "  FROM (log_docs INNER JOIN log ON log.id = log_docs.log_id)\n"
                . "    LEFT JOIN record ON record.record_id = log_docs.record_id\n"
                . "    LEFT JOIN subdef AS s ON s.record_id = log_docs.record_id AND s.name='document'\n"
                . "  WHERE (" . $filter['sql'] . ")\n"
                . "    AND log_docs.action = :action\n"
                . ") AS tt " . ($this->filter->getOrderFilter() ? $this->filter->getOrderFilter() : '') . "\n";

        return ['sql' => $this->sql, 'params' => $this->params];
    }
}
