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

class module_report_sqldownload extends module_report_sql implements module_report_sqlReportInterface
{
    protected $restrict = false;

    public function __construct(Application $app, module_report $report)
    {
        parent::__construct($app, $report);
        if ($report->isInformative()) {
            $this->restrict = true;
        }
    }

    public function buildSql()
    {
        $customFieldMap = [];

        $filter = $this->filter->getReportFilter() ? : ['params' => [], 'sql' => false];
        $this->params = array_merge([], $filter['params']);

        if ($this->groupby == false) {

            $this->sql = "SELECT log.id, log.user, log.societe, log.pays, log.activite, record.coll_id,\n"
                        . "  log.fonction, log.usrid, log_docs.date AS ddate, log_docs.record_id, log_docs.final, log_docs.comment\n"
                        . "FROM (log_docs INNER JOIN log ON log_docs.log_id = log.id)\n"
                        . "  LEFT JOIN record ON record.record_id=log_docs.record_id\n"
                        . "WHERE (" . $filter['sql'] . ")\n"
                        . "  AND !ISNULL(usrid)\n"
                        . "  AND (log_docs.action = 'download' OR log_docs.action = 'mail')\n"
                        . "  AND (log_docs.final = 'preview' OR log_docs.final = 'document')\n";

        } else {
            $name = $this->groupby;
            $field = $this->getTransQuery($this->groupby);

            if ($name == 'record_id' && $this->on == 'DOC') {
                $this->sql = "SELECT " . $name . ", SUM(1) AS telechargement, tt.comment, tt.final\n"
                            . "FROM (\n"
                            . "  SELECT log.id, TRIM(" . $field . ") AS " . $name . ", log_docs.comment, log_docs.final\n"
                            . "  FROM (log_docs FORCE INDEX(date) INNER JOIN log ON log_docs.log_id = log.id)\n"
                            . "    LEFT JOIN record ON log_docs.record_id = record.record_id\n";

                $customFieldMap = [
                    $field => $name,
                    'log_docs.comment'  => 'tt.comment',
                    'log_docs.final'    => 'tt.final',
                ];

            } elseif ($this->on == 'DOC') {
                $this->sql = "SELECT " . $name . ", SUM(1) AS telechargement\n"
                            . "FROM (\n"
                            . "  SELECT DISTINCT(log.id), TRIM(" . $field . ") AS " . $name . "\n"
                            . "  FROM (log_docs FORCE INDEX(date) INNER JOIN log ON log_docs.log_id = log.id)\n"
                            . "    LEFT JOIN record ON log_docs.record_id = record.record_id\n";

            } else {
                $this->sql = "SELECT " . $name . ", SUM(1) AS nombre\n"
                            . "FROM (\n"
                            . "  SELECT DISTINCT(log.id), TRIM(" . $this->getTransQuery($this->groupby) . ") AS " . $name . "\n"
                            . "  FROM (log_docs FORCE INDEX(date) INNER JOIN log ON log_docs.log_id = log.id)\n"
                            . "    LEFT JOIN record ON log_docs.record_id = record.record_id\n";
            }

            $this->sql .= "  WHERE " . $filter['sql'] . "\n"
                        . "    AND !ISNULL(usrid)\n"
                        . "    AND (log_docs.action = 'download' OR log_docs.action = 'mail')\n"
                        . "    AND " . ($this->on == 'DOC' ? "log_docs.final = 'document' " : "(log_docs.final = 'preview' OR log_docs.final = 'document')") . "\n"
                        . ") as tt\n"
                        . "GROUP BY " . $name . ($name == 'record_id' && $this->on == 'DOC' ? ", final" : "") . "\n";
        }

        $stmt = $this->connbas->prepare($this->sql);
        $stmt->execute($this->params);
        $this->total_row = $stmt->rowCount();
        $stmt->closeCursor();

        if (count($customFieldMap) > 0) {
            $this->sql .= $this->filter->getOrderFilter($customFieldMap) ? : '';
        } else {
            $this->sql .= $this->filter->getOrderFilter() ? : '';
        }

        if ($this->enable_limit) {
            $this->sql .= $this->filter->getLimitFilter() ? : '';
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
                    . "  FROM (log INNER JOIN log_docs ON log.id = log_docs.log_id)\n"
                    . "    LEFT JOIN record ON log_docs.record_id = record.record_id\n"
                    . "  WHERE (" . $filter['sql'] . ")\n"
                    . "    AND !ISNULL(log.usrid)\n"
                    . "    AND (log_docs.action = 'download' OR log_docs.action = 'mail')\n"
                    . "    AND " . ($this->on == 'DOC' ? "(log_docs.final =  'document')" : "(log_docs.final = 'preview' OR log_docs.final = 'document')") . "\n"
                    . ") AS tt\n";

        $this->sql .= $this->filter->getOrderFilter() ? : '';
        $this->sql .= $this->filter->getLimitFilter() ? : '';

        return ['sql' => $this->sql, 'params' => $this->params];
    }
}
